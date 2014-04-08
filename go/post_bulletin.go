package main

import (
	"flag"
	"fmt"
	"log"

	"code.google.com/p/google-api-go-client/youtube/v3"
)

var (
	message    = flag.String("message", "", "Text message to post")
	videoID    = flag.String("videoid", "", "ID of video to post")
	playlistID = flag.String("playlistid", "", "ID of playlist to post")
)

func main() {
	flag.Parse()

	// A bulletin must contain a message and may also contain a video or a
	// playlist. You can post a message with or without an accompanying video
	// or playlist, but you can't post a video and playlist at the same time.
	if *message == "" {
		log.Fatalf("Please provide a message.")
	}

	if *videoID != "" && *playlistID != "" {
		log.Fatalf("You cannot post a video and a playlist at the same time.")
	}

	client, err := buildOAuthHTTPClient(youtube.YoutubeScope)
	if err != nil {
		log.Fatalf("Error building OAuth client: %v", err)
	}

	service, err := youtube.New(client)
	if err != nil {
		log.Fatalf("Error creating YouTube client: %v", err)
	}

	// Start making YouTube API calls.
	parts := "snippet"
	bulletin := &youtube.Activity{
		Snippet: &youtube.ActivitySnippet{
			Description: *message,
		},
	}

	if *videoID != "" || *playlistID != "" {
		parts = "snippet,contentDetails"

		// The resource ID element value differs depending on
		// whether a playlist or a video is being posted.
		var resourceId *youtube.ResourceId
		switch {
		case *videoID != "":
			resourceId = &youtube.ResourceId{
				Kind:    "youtube#video",
				VideoId: *videoID,
			}
		case *playlistID != "":
			resourceId = &youtube.ResourceId{
				Kind:       "youtube#playlist",
				PlaylistId: *playlistID,
			}
		}

		bulletin.ContentDetails = &youtube.ActivityContentDetails{
			Bulletin: &youtube.ActivityContentDetailsBulletin{
				ResourceId: resourceId,
			},
		}
	}

	call := service.Activities.Insert(parts, bulletin)
	_, err = call.Do()
	if err != nil {
		log.Fatalf("Error making API call to post bulletin: %v", err.Error())
	}

	fmt.Println("The bulletin was posted to your channel.")
}
