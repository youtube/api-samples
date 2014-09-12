package main

import (
	"encoding/json"
	"errors"
	"flag"
	"fmt"
	"io/ioutil"
	"net"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"runtime"

	"code.google.com/p/goauth2/oauth"
)

const missingClientSecretsMessage = `
Please configure OAuth 2.0

To make this sample run you will need to populate the client_secrets.json file
found at:

   %v

with information from the APIs Console
https://code.google.com/apis/console#access

For more information about the client_secrets.json file format, please visit:
https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
`

var (
	clientSecretsFile = flag.String("secrets", "client_secrets.json", "Client Secrets configuration")
	cacheFile         = flag.String("cache", "request.token", "Token cache file")
)

// ClientConfig is data structure definition for client_secrets.json.
// This is what we'll unmarshal the JSON configuration file into.
type ClientConfig struct {
	ClientID     string   `json:"client_id"`
	ClientSecret string   `json:"client_secret"`
	RedirectURIs []string `json:"redirect_uris"`
	AuthURI      string   `json:"auth_uri"`
	TokenURI     string   `json:"token_uri"`
}

// Config is root level configuration object.
type Config struct {
	Installed ClientConfig `json:"installed"`
	Web       ClientConfig `json:"web"`
}

// openURL opens a browser window to that location.
// This code taken from:
//   http://stackoverflow.com/questions/10377243/how-can-i-launch-a-process-that-is-not-a-file-in-go
func openURL(url string) error {
	var err error
	switch runtime.GOOS {
	case "linux":
		err = exec.Command("xdg-open", url).Start()
	case "windows":
		err = exec.Command("rundll32", "url.dll,FileProtocolHandler", "http://localhost:4001/").Start()
	case "darwin":
		err = exec.Command("open", url).Start()
	default:
		err = fmt.Errorf("Cannot open URL %s on this platform", url)
	}
	return err
}

// readConfig reads the configuration from clientSecretsFile.
// Returns an oauth configuration object to be used with the Google API client
func readConfig(scope string) (*oauth.Config, error) {
	// Read the secrets file
	data, err := ioutil.ReadFile(*clientSecretsFile)
	if err != nil {
		pwd, _ := os.Getwd()
		fullPath := filepath.Join(pwd, *clientSecretsFile)
		return nil, fmt.Errorf(missingClientSecretsMessage, fullPath)
	}

	cfg := new(Config)
	err = json.Unmarshal(data, &cfg)
	if err != nil {
		return nil, err
	}

	var redirectUri string
	if len(cfg.Web.RedirectURIs) > 0 {
		redirectUri = cfg.Web.RedirectURIs[0]
	} else if len(cfg.Installed.RedirectURIs) > 0 {
		redirectUri = cfg.Installed.RedirectURIs[0]
	} else {
		return nil, errors.New("Must specify a redirect URI in config file or when creating OAuth client")
	}

	return &oauth.Config{
		ClientId:     cfg.Installed.ClientID,
		ClientSecret: cfg.Installed.ClientSecret,
		Scope:        scope,
		AuthURL:      cfg.Installed.AuthURI,
		TokenURL:     cfg.Installed.TokenURI,
		RedirectURL:  redirectUri,
		TokenCache:   oauth.CacheFile(*cacheFile),
		// This gives us a refresh token so we can use this access token indefinitely
		AccessType: "offline",
		// If we want a refresh token, we must set this to force an approval prompt or
		// this won't work
		ApprovalPrompt: "force",
	}, nil
}

// startWebServer starts a web server that listens on http://localhost:8080.
// The purpose of this webserver is to wait for a oauth code in the three-legged auth flow.
func startWebServer() (codeCh chan string, err error) {
	listener, err := net.Listen("tcp", "localhost:8080")
	if err != nil {
		return nil, err
	}
	codeCh = make(chan string)
	go http.Serve(listener, http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		code := r.FormValue("code")
		codeCh <- code // send code to OAuth flow
		listener.Close()
		w.Header().Set("Content-Type", "text/plain")
		fmt.Fprintf(w, "Received code: %v\r\nYou can now safely close this browser window.", code)
	}))

	return codeCh, nil
}

// buildOAuthHTTPClient takes the user through the three-legged OAuth flow.
// Opens a browser in the native OS or outputs a URL, blocking until the redirect
// completes to the /oauth2callback URI. Returns an instance of an HTTP client
// that can be passed to the constructor of the YouTube client.
func buildOAuthHTTPClient(scope string) (*http.Client, error) {
	config, err := readConfig(scope)
	if err != nil {
		msg := fmt.Sprintf("Cannot read configuration file: %v", err)
		return nil, errors.New(msg)
	}

	transport := &oauth.Transport{Config: config}

	// Try to read the token from the cache file.
	// If there's an error, the token is invalid or doesn't exist, do the 3-legged OAuth flow.
	token, err := config.TokenCache.Token()
	if err != nil {
		// Start web server.
		// This is how this program receives the authorization code when the browser redirects.
		codeCh, err := startWebServer()
		if err != nil {
			return nil, err
		}

		// Open url in browser
		url := config.AuthCodeURL("")
		err = openURL(url)
		if err != nil {
			fmt.Println("Visit the URL below to get a code.",
				" This program will pause until the site is visted.")
		} else {
			fmt.Println("Your browser has been opened to an authorization URL.",
				" This program will resume once authorization has been provided.\n")
		}
		fmt.Println(url)

		// Wait for the web server to get the code.
		code := <-codeCh

		// This will take care of caching the code on the local filesystem, if necessary,
		// as long as the TokenCache attribute in the config is set
		token, err = transport.Exchange(code)
		if err != nil {
			return nil, err
		}
	}

	transport.Token = token
	return transport.Client(), nil
}
