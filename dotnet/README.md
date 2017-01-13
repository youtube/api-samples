## Samples in this directory:

### [Create a playlist](/dotnet/PlaylistUpdates.cs)

Method: youtube.playlists.insert<br>
Description: The following code sample calls the API's <code>playlists.insert</code> method to create a private playlist
owned by the channel authorizing the request.

### [Retrieve my uploads](/dotnet/MyUploads.cs)

Method: youtube.playlistItems.list<br>
Description: The following code sample calls the API's <code>playlistItems.list</code> method to retrieve a list of videos
uploaded to the channel associated with the request. The code also calls the <code>channels.list</code> method with the
<code>mine</code> parameter set to <code>true</code> to retrieve the playlist ID that identifies the channel's uploaded
videos.

### [Search by keyword](/dotnet/Search.cs)

Method: youtube.search.list<br>
Description: The following code sample calls the API's <code>search.list</code> method to retrieve search results
associated with a particular keyword.

### [Upload a video](/dotnet/UploadVideo.cs)

Method: youtube.videos.insert<br>
Description: The following code sample calls the API's <code>videos.insert</code> method to upload a video to the channel
associated with the request.
