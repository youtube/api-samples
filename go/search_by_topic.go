package main

import (
	"encoding/json"
	"errors"
	"flag"
	"fmt"
	"io/ioutil"
	"log"
	"net/http"
	"net/url"
	"os"

	"code.google.com/p/google-api-go-client/googleapi/transport"
	"code.google.com/p/google-api-go-client/youtube/v3"
)

var (
	query      = flag.String("query", "Google", "Freebase search term")
	maxResults = flag.Int64("max-results", 25, "Max YouTube results")
	resultType = flag.String("type", "channel", "YouTube result type: video, playlist, or channel")
)

const developerKey = "YOUR DEVELOPER KEY HERE"
const freebaseSearchURL = "https://www.googleapis.com/freebase/v1/search?%s"

// Notable is struct for unmarshalling JSON values from the API.
type Notable struct {
	Name string
	ID   string
}

// FreebaseTopic is struct for unmarshalling JSON values from the API.
type FreebaseTopic struct {
	Mid     string
	ID      string
	Name    string
	Notable Notable
	Lang    string
	Score   float64
}

// FreebaseResponse is struct for unmarshalling JSON values from the freebase API.
type FreebaseResponse struct {
	Status string
	Result []FreebaseTopic
}

func main() {
	flag.Parse()

	topicID, err := getTopicID(*query)
	if err != nil {
		log.Fatalf("Cannot fetch topic ID from Freebase: %v", err)
	}

	err = youtubeSearch(topicID)
	if err != nil {
		log.Fatalf("Cannot make YouTube API call: %v", err)
	}
}

// getTopicID queries Freebase with the given string. Prompts user
// to select a topic, then returns to search YouTube for videos,
// channels or playlists with the given topic.
func getTopicID(topic string) (string, error) {
	urlParams := url.Values{
		"query": []string{topic},
		"key":   []string{developerKey},
	}

	apiURL := fmt.Sprintf(freebaseSearchURL, urlParams.Encode())

	resp, err := http.Get(apiURL)
	if err != nil {
		return "", err
	} else if resp.StatusCode != http.StatusOK {
		errorMsg := fmt.Sprintf("Received HTTP status code %v using developer key: %v",
			resp.StatusCode, developerKey)
		return "", errors.New(errorMsg)
	}

	body, err := ioutil.ReadAll(resp.Body)
	if err != nil {
		return "", err
	}

	var data FreebaseResponse
	err = json.Unmarshal(body, &data)
	if err != nil {
		return "", nil
	}

	if len(data.Result) == 0 {
		return "", errors.New("No matching terms were found in Freebase.")
	}

	// Print a list of topics for the user to select
	fmt.Println("The following topics were found:")
	for index, topic := range data.Result {
		if topic.Notable.Name == "" {
			topic.Notable.Name = "Unknown"
		}
		fmt.Printf("   %2d. %s (%s)\r\n", index+1, topic.Name, topic.Notable.Name)
	}

	prompt := fmt.Sprintf("Enter a topic number to find related YouTube %s [1-%v]: ",
		*resultType, len(data.Result))
	selection, err := readInt(prompt, 1, len(data.Result))
	if err != nil {
		return "", nil
	}
	choice := data.Result[selection-1]
	return choice.Mid, nil
}

// readInt reads an integer from standard input, validating that the input is equal to or
// greater than the min, while being equal to or lesser than the max value.
func readInt(prompt string, min int, max int) (int, error) {
	// Loop until we have a valid input.
	for {
		fmt.Print(prompt)
		var i int
		_, err := fmt.Fscan(os.Stdin, &i)
		if err != nil {
			return 0, err
		}
		if i < min || i > max {
			fmt.Println("Invalid input.")
			continue
		}
		return i, nil
	}
}

// youtubeSearch searches YouTube for the topic given in the query flag and prints the results.
// Takes a mid parameter as supplied by Freebase.
func youtubeSearch(mid string) error {
	client := &http.Client{
		Transport: &transport.APIKey{Key: developerKey},
	}

	service, err := youtube.New(client)
	if err != nil {
		return err
	}

	// Make the API call to YouTube
	call := service.Search.List("id,snippet").
		TopicId(mid).
		Type(*resultType).
		MaxResults(*maxResults)
	response, err := call.Do()
	if err != nil {
		return err
	}

	// Iterate through each item and output it
	for _, item := range response.Items {
		itemID := ""
		switch item.Id.Kind {
		case "youtube#video":
			itemID = item.Id.VideoId
		case "youtube#channel":
			itemID = item.Id.ChannelId
		case "youtube#playlist":
			itemID = item.Id.PlaylistId
		}
		fmt.Printf("%v (%v)\r\n", item.Snippet.Title, itemID)
	}
	return nil
}
