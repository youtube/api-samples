Recommended Go version: latest version

To run these code samples, you will need to install the dependent libraries via
the "go get" command. See the client library's getting started guide for more detail:
https://github.com/google/google-api-go-client/blob/master/GettingStarted.md

You also need to enable the YouTube Data API for the project associated with your developer
credentials.

## Authorization credentials
To run any sample that does not require user authorization, such as search\_by\_keyword.go,
you need to replace the value of the `developerKey` constant with a valid API key:

```
const developerKey = "YOUR DEVELOPER KEY"
```

To run any sample that requires authorization on behalf of a user, such as retrieving the
authenticated user's uploads, you need an OAuth 2.0 client ID and client secret pair. These
can be created at the Google API console at https://developers.google.com/console. After
creating your OAuth 2.0 credentials, download the client\_secret.json file to the directory
in which you are running these samples.

## Running samples

Samples can be run with the standard "go run" command as long as your API key or OAuth 2.0
credentials are in place. The samples use the `errors.go` file to
print out API errors, so you need to also include that file in the "go run" command. Samples
that require authorization also require the `oauth2.go` file to be included in the
"go run" command:

Example usages:

```
   go run search_by_keyword.go errors.go
   go run my_uploads.go errors.go oauth2.go
   go run upload_video.go errors.go oauth2.go --filename="sample_video.flv" --title="Test video" --keywords="golang test"
```

More information about the YouTube APIs can be found at https://developers.google.com/youtube.

## Samples in this directory:

### [Authorize a request](/go/oauth2.go)

Description: This code sample performs OAuth 2.0 authorization by checking for the presence of a local file that
contains authorization credentials. If the file is not present, the script opens a browser and waits for a response,
then saves the returned credentials locally.

### [List playlists](/go/playlists.go)

Methods: youtube.playlists.list<br>
Description: This code sample calls the API's `playlists.list` method. Use command-line flags to define the parameters you want to use in the request as shown in the following examples:</p>
 
```
# Retrieve playlists for a specified channel
go run playlists.go oauth.go errors.go --channelId=UC_x5XG1OV2P6uZZ5FSM9Ttw

# Retrieve authenticated user's playlists
go run playlists.go oauth.go errors.go --mine=true
```

### [Retrieve my uploads](/go/my_uploads.go)

Methods: youtube.channels.list, youtube.playlistItems.list<br>
Description: This code sample calls the API's <code>playlistItems.list</code> method to retrieve a list of 
videos uploaded to the channel associated with the request. The code also calls the <code>channels.list</code> 
method with the <code>mine</code> parameter set to <code>true</code> to retrieve the playlist ID that identifies 
the channel's uploaded videos.

### [Search by keyword](/go/search_by_keyword.go)

Method: youtube.search.list<br>
Description: This code sample calls the API's <code>search.list</code> method to retrieve search results associated
with a particular keyword.

### [Upload a video](/go/upload_video.go)

Method: youtube.videos.insert<br>
Description: This code sample calls the API's <code>videos.insert</code> method to upload a video to the channel
associated with the request.
