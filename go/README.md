Minimum Go version: go 1.1+

To run these code samples, you will need to install the dependent libraries via
the "go get" command. See the client library's getting started guide for more detail:
https://github.com/google/google-api-go-client/blob/master/GettingStarted.md

The keyword search and topic search samples can be run via the standard "go run" command
once the developerKey constant is populated with an API key created at
https://developers.google.com/console.

Example usage:

   go run search\_by\_keyword.go

The YouTube Data API must be enabled for the project associated with this key.

To run any sample that requires authorization on behalf of a user, such as checking
for uploads, this requires the shared oauth2.go file to be passed as a parameter to "go run".
These samples require an OAuth 2.0 client ID and client secret pair, which can
also be created at the Google API console at https://developers.google.com/console. After
creating your OAuth 2.0 credentials, download the client\_secret.json file to the directory
in which you are running these samples.

Example usage:

   go run my\_uploads.go oauth2.go

The **oauth2.go** file contains code that is shared between the code samples that require
OAuth 2.0 authorization, so it must be passed as a parameter to "go run".

More information about the YouTube APIs can be found at https://developers.google.com/youtube.

## Samples in this directory:

### [Authorize a request](/go/oauth2.go)

Description: This code sample performs OAuth 2.0 authorization by checking for the presence of a local file that
contains authorization credentials. If the file is not present, the script opens a browser and waits for a response,
then saves the returned credentials locally.

### [Retrieve my uploads](/go/my_uploads.go)

Method: youtube.playlistItems.list<br>
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
