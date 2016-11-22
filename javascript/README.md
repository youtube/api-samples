## Samples in this directory:

### [Authorize a request](/youtube/api-samples/blob/master/javascript/auth.js)

Description: The <code>auth.js</code> script demonstrates how to use the Google APIs Client Library for JavaScript
to provide API access and authorize user requests. All of the subsequent samples on this page use this script to
authorize their requests.<br><br>
For requests that do not require authentication, you could also use the
<code>key</code> query parameter to specify an API key rather than using OAuth 2.0.<br><br>
<strong>Note:</strong> You need to update the client ID in the <code>auth.js</code> file. You can obtain your own
client ID by registering your application in the
<a href="https://console.developers.google.com">Google Developers Console</a>.

### [Do resumable uploads with CORS](/youtube/api-samples/blob/master/javascript/cors_upload.js)

Description: This code sample demonstrates how to execute a resumable upload using XHR/CORS.

### [Upload a video](/youtube/api-samples/blob/master/javascript/upload_video.js)

Method: youtube.videos.insert<br>
Description: This code sample calls the API's <code>videos.insert</code> method to upload a video to the channel
associated with the request.

### [Retrieve my uploads](/youtube/api-samples/blob/master/javascript/my_uploads.js)

Method: youtube.playlistItems.list<br>
Description: This code sample calls the API's <code>playlistItems.list</code> method to retrieve a list of 
videos uploaded to the channel associated with the request. The code also calls the <code>channels.list</code> 
method with the <code>mine</code> parameter set to <code>true</code> to retrieve the playlist ID that identifies 
the channel's uploaded videos.

### [Search by keyword](/youtube/api-samples/blob/master/javascript/search.js)

Method: youtube.search.list<br>
Description: This code sample calls the API's <code>search.list</code> method to retrieve search results associated
with a particular keyword.

### [Create a playlist](/youtube/api-samples/blob/master/javascript/playlist_updates.js)

Method: youtube.playlists.insert<br>
Description: This sample creates a private playlist and add videos to it. (You could, of course, modify the code so
that it creates a publicly visible playlist or so that it checks a form value to determine whether the playlist is
public or private.) Note that you need to update the client ID in the <code>auth.js</code> file to run this code.

### [Calling the Analytics API](/youtube/api-samples/blob/master/javascript/analytics_codelab.js)

Method: youtubeAnalytics.reports.query<br>
Description: This sample uses the YouTube Data and YouTube Analytics APIs to retrieve YouTube channel metrics.
The samples use the <a target="_blank" href="/api-client-library/javascript/">Google APIs JavaScript client library</a>
to demonstrate API functionality. The <a href="/youtube/analytics/v1/sample-application">Building a Sample Application</a>
document walks you through the steps of building this application and discusses different portions of this code in more 
detail.
