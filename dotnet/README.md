## Samples in this directory:

### [Upload a video](/youtube/api-samples/blob/master/dotnet/UploadVideo.cs)

Method: youtube.videos.insert<br>
Description: The following code sample calls the API's <code>videos.insert</code> method to upload a video to the channel
associated with the request.

### [Retrieve my uploads](/youtube/api-samples/blob/master/dotnet/MyUploads.go)

Method: youtube.playlistItems.list<br>
Description: The following code sample calls the API's <code>playlistItems.list</code> method to retrieve a list of videos
uploaded to the channel associated with the request. The code also calls the <code>channels.list</code> method with the
<code>mine</code> parameter set to <code>true</code> to retrieve the playlist ID that identifies the channel's uploaded
videos.

### [Search by keyword](/youtube/api-samples/blob/master/dotnet/Search.cs)

Method: youtube.search.list<br>
Description: The following code sample calls the API's <code>search.list</code> method to retrieve search results
associated with a particular keyword.

### [Create a playlist](/youtube/api-samples/blob/master/dotnet/PlaylistUpdates.cs)

Method: youtube.playlists.insert<br>
Description: The following code sample calls the API's <code>playlists.insert</code> method to create a private playlist
owned by the channel authorizing the request.

### [Retrieve a content owner's managed channels](/youtube/api-samples/blob/master/dotnet/papi_my_managed_channels.cs)

Method: youtube.channels.list
Description: The following code sample calls the YouTube Data API's <code>channels.list</code> method to retrieve a list
of channels managed by the content owner making the API request.
