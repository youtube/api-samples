## Samples in this directory:

### [Authorize a request](/javascript/auth.js)

Description: The <code>auth.js</code> script demonstrates how to use the Google APIs Client Library for JavaScript
to provide API access and authorize user requests. All of the subsequent samples on this page use this script to
authorize their requests.<br><br>
For requests that do not require authentication, you could also use the
<code>key</code> query parameter to specify an API key rather than using OAuth 2.0.<br><br>
<strong>Note:</strong> You need to update the client ID in the <code>auth.js</code> file. You can obtain your own
client ID by registering your application in the
<a href="https://console.developers.google.com">Google Developers Console</a>.

### [Do resumable uploads with CORS](/javascript/cors_upload.js)

Method: youtube.videos.insert
Description: This code sample demonstrates how to execute a resumable upload using XHR/CORS.

### [Create a playlist](/javascript/playlist_updates.js)

Method: youtube.playlists.insert<br>
Description: This JavaScript code creates a private playlist and adds videos to that playlist. (You could, of course, modify the code so that it creates a publicly visible playlist or so that it checks a form value to determine whether the playlist is
public or private.) Note that you need to update the client ID in the <code>auth.js</code> file to run this code.<br><br>The HTML page uses JQuery, along with the <code>auth.js</code> and <code>playlist_updates.js</code> JavaScript files, to display a simple form for adding videos to the playlist.

### [Retrieve my uploads](/javascript/my_uploads.js)

Method: youtube.playlistItems.list<br>
Description: The JavaScript sample code performs the following functions:<br>
<ol>
  <li>It retrieves the playlist ID for videos uploaded to the user's channel using the API's <code>channels.list</code> method. This API call also sets the <code>mine</code> parameter to <code>true</code> to retrieve channel information for the authorizing user.</li>
  <li>It passes that ID to the <code>playlistItems.list</code> method to retrieve the videos in that list.</li>
  <li>It displays the list of videos.</li>
  <li>It constructs next and previous page buttons and sets their visibility based on the information in the API response.</li>
</ol>

The HTML page uses JQuery, the <code>auth.js</code> and <code>my_uploads.js</code> JavaScript files, and a CSS file to display the list of uploaded videos.

### [Search by keyword](/javascript/search.js)

Method: youtube.search.list<br>
Description: This code sample calls the API's <code>search.list</code> method to retrieve search results associated
with a particular keyword. The HTML page uses JQuery, along with the <code>auth.js</code> and <code>search.js</code> JavaScript files, to show a simple search form and display the list of search results.

### [Upload a video](/javascript/upload_video.js)

Method: youtube.videos.insert<br>
Description: This JavaScript sample performs the following functions:<br>
<ol>
  <li>It retrieves the channel name and thumbnail of the authenticated user's channel using the API's channels.list method.</li>
  <li>It handles the video upload to YouTube using the resumable upload protocol.</li>
  <li>It polls for the uploaded video's upload and processing status using the API's videos.list method by setting the part parameter value to status.</li>
</ol>

The HTML page uses JQuery, the <code>cors_upload.js</code> and <code>upload_video.js</code> JavaScript files, and the
<code>upload_video.css</code> file to upload a video file to YouTube.<br><br>Note that if you use this code in your own application, you must replace the value of the <code>data-clientid</code> attribute in the code for the Sign-In Button
with your project's client ID. The only valid JavaScript origin for the client ID in the sample code is
<code>http://localhost</code>. This means that you could test the sample locally, but it would not work in your
production application.

### [Calling the Analytics API](/javascript/analytics_codelab.js)

Method: youtubeAnalytics.reports.query<br>
Description: This sample uses the YouTube Data and YouTube Analytics APIs to retrieve YouTube channel metrics.
The samples use the <a target="_blank" href="/api-client-library/javascript/">Google APIs JavaScript client library</a>
to demonstrate API functionality. The <a href="/youtube/analytics/v1/sample-application">Building a Sample Application</a>
document walks you through the steps of building this application and discusses different portions of this code in more 
detail.<br><br>

In addition to the JavaScript file described above, the HTML page uses <a href="http://jquery.com">jQuery</a>, which
provides helper methods to simplify HTML document traversing, event handling, animating and Ajax interactions. It also
uses the <a href="https://developers.google.com/loader/">Google API loader</a> (<code>www.google.com/jsapi</code>),
which lets you easily import one or more Google APIs. This example uses the API loader to load the Google Visualization API,
which is used to chart the retrieved Analytics data. Finally, the
<a href="/api-client-library/javascript/features/authentication">Google APIs Client Library for JavaScript</a>
helps you to implement OAuth 2.0 authentication and to call the YouTube Analytics API.
