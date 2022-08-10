package main

import (
	"context"
	"log"
	"time"

	"google.golang.org/api/option"
	"google.golang.org/api/youtube/v3"
)

func insertBroadcast(service *youtube.Service, title string, startTime time.Time, privacyStatus string) (*youtube.LiveBroadcast, error) {
	liveBroadcast := &youtube.LiveBroadcast{
		Snippet: &youtube.LiveBroadcastSnippet{
			Title:              title,
			ScheduledStartTime: startTime.Format(time.RFC3339),
		},
		Status: &youtube.LiveBroadcastStatus{
			PrivacyStatus: privacyStatus,
		},
	}
	return service.LiveBroadcasts.Insert([]string{"snippet", "status"}, liveBroadcast).Do()
}

func insertStream(service *youtube.Service, title, resolution, ingestionType, frameRate string) (*youtube.LiveStream, error) {
	liveStream := &youtube.LiveStream{
		Snippet: &youtube.LiveStreamSnippet{
			Title: title,
		},
		Cdn: &youtube.CdnSettings{
			Resolution:    resolution,
			IngestionType: ingestionType,
			FrameRate:     frameRate,
		},
	}
	return service.LiveStreams.Insert([]string{"snippet", "cdn", "status"}, liveStream).Do()
}

func bindBroadcast(service *youtube.Service, broadcastID, streamID string) (*youtube.LiveBroadcast, error) {
	return service.LiveBroadcasts.Bind(broadcastID, []string{"id", "status", "contentDetails"}).StreamId(streamID).Do()
}

func transitBroadcast(service *youtube.Service, broadcastID, status string) (*youtube.LiveBroadcast, error) {
	return service.LiveBroadcasts.Transition(status, broadcastID, []string{"id", "snippet", "contentDetails", "status"}).Do()
}

func main() {
	ctx := context.Background()
	client := getClient(youtube.YoutubeForceSslScope)
	service, err := youtube.NewService(ctx, option.WithHTTPClient(client))
	if err != nil {
		log.Fatalf("error in creating service %s", err.Error())
	}

	broadcast, err := insertBroadcast(service, "my first golang broadcast", time.Now(), "public")
	if err != nil {
		log.Fatalf("error in inserting broadcast %s", err.Error())
	}

	stream, err := insertStream(service, "my first golang stream", "variable", "rtmp", "variable")
	if err != nil {
		log.Fatalf("error in inserting stream %s", err.Error())
	}

	broadcast, err = bindBroadcast(service, broadcast.Id, stream.Id)
	if err != nil {
		log.Fatalf("error in binding broadcast to stream %s", err.Error())
	}

	ingestionAddr := stream.Cdn.IngestionInfo.IngestionAddress + "/" + stream.Cdn.IngestionInfo.StreamName
	log.Println("you can now start ingestion at ", ingestionAddr)
	// start streaming
	time.Sleep(30 * time.Second) // waiting for stream to start. NOTE: This is not required
	_, err = transitBroadcast(service, broadcast.Id, "live")
	if err != nil {
		log.Fatalf("error while broadcasting live %s", err.Error())
	}
}
