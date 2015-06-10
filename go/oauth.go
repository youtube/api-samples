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
	"time"

	"golang.org/x/oauth2"
)

const missingClientSecretsMessage = `
Please configure OAuth 2.0

To make this sample run, you need to populate the client_secrets.json file
found at:

   %v

with information from the {{ Google Cloud Console }}
{{ https://cloud.google.com/console }}

For more information about the client_secrets.json file format, please visit:
https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
`

var (
	clientSecretsFile = flag.String("secrets", "client_secrets.json", "Client Secrets configuration")
	cache             = flag.String("cache", "request.token", "Token cache file")
)

// CallbackStatus is returned from the oauth2 callback
type CallbackStatus struct {
	code  string
	state string
	err   error
}

// Cache specifies the methods that implement a Token cache.
type Cache interface {
	Token() (*oauth2.Token, error)
	PutToken(*oauth2.Token) error
}

// CacheFile implements Cache. Its value is the name of the file in which
// the Token is stored in JSON format.
type CacheFile string

// ClientConfig is a data structure definition for the client_secrets.json file.
// The code unmarshals the JSON configuration file into this structure.
type ClientConfig struct {
	ClientID     string   `json:"client_id"`
	ClientSecret string   `json:"client_secret"`
	RedirectURIs []string `json:"redirect_uris"`
	AuthURI      string   `json:"auth_uri"`
	TokenURI     string   `json:"token_uri"`
}

// Config is a root-level configuration object.
type Config struct {
	Installed ClientConfig `json:"installed"`
	Web       ClientConfig `json:"web"`
}

// openURL opens a browser window to the specified location.
// This code originally appeared at:
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
// It returns an oauth configuration object for use with the Google API client.
func readConfig(scope string) (*oauth2.Config, error) {
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

	return &oauth2.Config{
		ClientID:     cfg.Installed.ClientID,
		ClientSecret: cfg.Installed.ClientSecret,
		Scopes:       []string{scope},
		Endpoint: oauth2.Endpoint{
			AuthURL:  cfg.Installed.AuthURI,
			TokenURL: cfg.Installed.TokenURI,
		},
		RedirectURL: redirectUri,
	}, nil
}

// startWebServer starts a web server that listens on http://localhost:8080.
// The webserver waits for an oauth code in the three-legged auth flow.
func startWebServer() (callbackCh chan CallbackStatus, err error) {
	listener, err := net.Listen("tcp", "localhost:8080")
	if err != nil {
		return nil, err
	}
	callbackCh = make(chan CallbackStatus)
	go http.Serve(listener, http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		cbs := CallbackStatus{}
		cbs.state = r.FormValue("state")
		cbs.code = r.FormValue("code")
		callbackCh <- cbs // send code to OAuth flow
		listener.Close()
		w.Header().Set("Content-Type", "text/plain")
		fmt.Fprintf(w, "Received code: %v\r\nYou can now safely close this browser window.", cbs.code)
	}))

	return callbackCh, nil
}

// buildOAuthHTTPClient takes the user through the three-legged OAuth flow.
// It opens a browser in the native OS or outputs a URL, then blocks until
// the redirect completes to the /oauth2callback URI.
// It returns an instance of an HTTP client that can be passed to the
// constructor of the YouTube client.
func buildOAuthHTTPClient(scope string) (*http.Client, error) {
	config, err := readConfig(scope)
	if err != nil {
		msg := fmt.Sprintf("Cannot read configuration file: %v", err)
		return nil, errors.New(msg)
	}

	// Try to read the token from the cache file.
	// If an error occurs, do the three-legged OAuth flow because
	// the token is invalid or doesn't exist.
	tokenCache := CacheFile(*cache)
	token, err := tokenCache.Token()
	if err != nil {

		// You must always provide a non-zero string and validate that it matches
		// the state query parameter on your redirect callback
		randState := fmt.Sprintf("st%d", time.Now().UnixNano())

		// Start web server.
		// This is how this program receives the authorization code
		// when the browser redirects.
		callbackCh, err := startWebServer()
		if err != nil {
			return nil, err
		}

		url := config.AuthCodeURL(randState, oauth2.AccessTypeOffline, oauth2.ApprovalForce)
		err = openURL(url)
		if err != nil {
			fmt.Println("Visit the URL below to get a code.",
				" This program will pause until the site is visted.")
		} else {
			fmt.Println("Your browser has been opened to an authorization URL.",
				" This program will resume once authorization has been provided.")
		}
		fmt.Println(url)

		// Wait for the web server to get the code.
		cbs := <-callbackCh

		if cbs.state != randState {
			return nil, fmt.Errorf("expecting state '%s', received state '%s'", randState, cbs.state)
		}

		token, err = config.Exchange(oauth2.NoContext, cbs.code)
		if err != nil {
			return nil, err
		}
		err = tokenCache.PutToken(token)
		if err != nil {
			return nil, err
		}
	}

	return config.Client(oauth2.NoContext, token), nil
}

// Token retreives the token from the token cache
func (f CacheFile) Token() (*oauth2.Token, error) {
	file, err := os.Open(string(f))
	if err != nil {
		return nil, fmt.Errorf("CacheFile.Token: %s", err.Error())
	}
	defer file.Close()
	tok := &oauth2.Token{}
	if err := json.NewDecoder(file).Decode(tok); err != nil {
		return nil, fmt.Errorf("CacheFile.Token: %s", err.Error())
	}
	return tok, nil
}

// PutToken stores the token in the token cache
func (f CacheFile) PutToken(tok *oauth2.Token) error {
	file, err := os.OpenFile(string(f), os.O_RDWR|os.O_CREATE|os.O_TRUNC, 0600)
	if err != nil {
		return fmt.Errorf("CacheFile.PutToken: %s", err.Error())
	}
	if err := json.NewEncoder(file).Encode(tok); err != nil {
		file.Close()
		return fmt.Errorf("CacheFile.PutToken: %s", err.Error())
	}
	if err := file.Close(); err != nil {
		return fmt.Errorf("CacheFile.PutToken: %s", err.Error())
	}
	return nil
}
