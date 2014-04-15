package main

import (
	"flag"
	"fmt"
	"log"

	"code.google.com/p/google-api-go-client/youtube/v3"
)

func main() {
	flag.Parse()

	client, err := buildOAuthHTTPClient(youtube.YoutubeReadonlyScope)
	if err != nil {
		log.Fatalf("Error building OAuth client: %v", err)
	}

	service, err := youtube.New(client)
	if err != nil {
		log.Fatalf("Error creating YouTube client: %v", err)
	}

	// Starting making YouTube API calls
	call := service.Channels.List("contentDetails").Mine(true)

	response, err := call.Do()
	if err != nil {
		log.Fatalf("Error making API call to list channels: %v", err.Error())
	}

	for _, channel := range response.Items {
		playlistId := channel.ContentDetails.RelatedPlaylists.Uploads
		fmt.Printf("Videos in list %s\r\n", playlistId)

		nextPageToken := ""
		for {
			playlistCall := service.PlaylistItems.List("snippet").
				PlaylistId(playlistId).
				MaxResults(50).
				PageToken(nextPageToken)

			playlistResponse, err := playlistCall.Do()

			if err != nil {
				log.Fatalf("Error fetching playlist items: %v", err.Error())
			}

			for _, playlistItem := range playlistResponse.Items {
				title := playlistItem.Snippet.Title
				videoId := playlistItem.Snippet.ResourceId.VideoId
				fmt.Printf("%v, (%v)\r\n", title, videoId)
			}

			nextPageToken = playlistResponse.NextPageToken
			if nextPageToken == "" {
				break
			}
			fmt.Println()
		}
	}
}
