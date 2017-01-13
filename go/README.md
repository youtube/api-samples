Minimum Go version: go 1.1+

To run these code samples, you will need to install the dependent libraries via
the "go get" command. These code samples require the goauth2 and google-api-go-client
libraries which can be installed with the following commands:

   go get code.google.com/p/goauth2/oauth
   go get code.google.com/p/google-api-go-client/youtube/v3

The keyword search and topic search samples can be run via the standard "go run" command
once the developerKey constant is populated with an API key created at
https://code.google.com/apis/console.

Example usage:

   go run search_key_keyword.go

The YouTube Data API must be enabled for the project associated with this key.

To run any sample that requires authorization on behalf of a user, such as checking
for uploads, this requires the shared oauth.go file to be passed as a parameter to "go run".
These samples require a "Web Application" client ID and client secret pair which can
also be created at the Google API console at https://code.google.com/apis/console. Once
a client ID and secret pair have been created, these values must be populated into
client_secrets.json in the corresponding fields. A template client_secrets.json has been
provided in client_secrets.json.sample. Rename this file and populate the fields.

Example usage:

   go run my_uploads.go oauth.go

oauth.go contains code that is shared between the code samples that require OAuth 2.0 
authorization, so it must be passed as a parameter to "go run".

More information about the YouTube APIs can be found at https://developer.google.com/youtube.

## Samples in this directory:

### [Authorize a request](/go/oauth.go)

Description: This code sample performs OAuth 2.0 authorization by checking for the presence of a local file that
contains authorization credentials. If the file is not present, the script opens a browser and waits for a response,
then saves the returned credentials locally.

### [Post a channel bulletin](/go/post_bulletin.go)

Method: youtube.activities.insert<br>
Description: This code sample calls the API's <code>activities.insert</code> method to post a bulletin to the
channel associated with the request.

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
