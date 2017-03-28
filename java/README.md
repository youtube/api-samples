Prerequisites for this code sample:
- Java 1.6
- Apache Maven (http://maven.apache.org)

Before running the sample, client_secrets.json must be populated with a
client ID and client secret. You can create an ID/secret pair at:

  https://code.google.com/apis/console

To build this code sample from the command line, type:

  <code>mvn compile</code>

To run a code sample from the command line, enter the following:

  <code>mvn exec:java -Dexec.mainClass="FULL_CLASS_NAME"</code>

For samples that require arguments, also specify -Dexec.args, e.g.:

  <code>mvn exec:java -Dexec.mainClass="FULL_CLASS_NAME" -Dexec.args="arg1 arg2"</code>

For more instructions about how to set up Maven and/or your IDE to run
YouTube API samples, see this video:

   http://youtu.be/pb_t5_ShQOM

## Samples in this directory:

### [Authorize a request](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/Auth.java)

Description: This sample demonstrates how to use OAuth 2.0 to authorize an application to access resources on a user's behalf. 

### [Add a channel subscription](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/AddSubscription.java)

Method: youtube.subscriptions.insert<br>
Description: This sample calls the API's <code>subscriptions.insert</code> method to add a subscription to a specified
channel.

### [Add a featured video](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/InvideoProgramming.java)

Method: youtube.channels.update<br>
Description: This sample calls the API's <code>channels.update</code> method to set <code>invideoPromotion</code>
properties for the channel.

### [Create a playlist](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/PlaylistUpdates.java)

Method: youtube.playlists.insert<br>
Description: This sample calls the API's <code>playlists.insert</code> method to create a private playlist owned by the
channel authorizing the request.

### [Create and manage comments](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/CommentHandling.java)

Method: youtube.commentThreads.list, youtube.comments.insert, youtube.comments.list, youtube.comments.update,
youtube.comments.setModerationStatus, youtube.comments.markAsSpam, youtube.comments.delete<br>
Description: This sample demonstrates how to use the following API methods to create and manage comments:<br>
<ul>
<li>It calls the <code>commentThreads.list</code> method with the <code>videoId</code> parameter set to retrieve comments
for a video.</li>
<li>It calls the <code>comments.insert</code> method with the <code>parentId</code> parameter set to reply to an existing
comment.</li>
<li>It calls the <code>comments.list</code> method with the <code>parentId</code> parameter to retrieve the comments in the
thread.</li>
<li>It calls the <code>comments.update</code> method with comment in the request body to update a comment.</li>
<li>It calls the <code>comments.setModerationStatus</code> method to set the moderation status of the comment, the
<code>comments.markAsSpam</code> method to mark the comment as spam, and the <code>comments.delete</code> method to
delete the comment, using the <code>id</code> parameter to identify the comment.</li></ul>

### [Create and manage comment threads](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/CommentThreads.java)

Method: youtube.commentThreads.insert, youtube.commentThreads.list, youtube.commentThreads.update<br>
Description: This sample demonstrates how to use the following API methods to create and manage top-level comments:<br>
<ul>
<li>It calls the <code>commentThreads.insert</code> method once with the <code>channelId</code> parameter to create a
channel comment and once with the <code>videoId</code> parameter to create a video comment.</li>
<li>It calls the <code>commentThreads.list</code> method once with the <code>channelId</code> parameter to retrieve
channel comments and once with the <code>videoId</code> parameter to retrieve video comments.</li>
<li>It calls the <code>commentThreads.update</code> method once to update a video comment and then again to update a
channel comment. In each case, the request body contains the <code>comment</code> resource being updated.</li>
</ul>

### [Create and manage YouTube video caption tracks](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/Captions.java)

Method: youtube.captions.insert, youtube.captions.list, youtube.captions.update, youtube.captions.download,
youtube.captions.delete<br>
Description: This sample demonstrates how to use the following API methods to create and manage YouTube video caption
tracks:<br>
<ul>
<li>It calls the <code>captions.insert</code> method with the <code>isDraft</code> parameter set to <code>true</code>
to upload a caption track in draft status.</li>
<li>It calls the <code>captions.list</code> method with the <code>videoId</code> parameter to retrieve video caption
tracks.</li>
<li>It calls the <code>captions.update</code> method with the caption in the request body to update a caption track.</li>
<li>It calls the <code>captions.download</code> method to download the caption track.</li>
<li>It calls the <code>captions.delete</code> method to delete the caption track, using the <code>id</code> parameter to
identify the caption track.</li>
</ul>

### [Post a channel bulletin](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/ChannelBulletin.java)

Method: youtube.activities.insert<br>
Description: This sample calls the API's <code>activities.insert</code> method to post a bulletin to the channel
associated with the request.

### [Retrieve my uploads](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/MyUploads.java)

Method: youtube.playlistItems.list<br>
Description: This sample calls the API's <code>playlistItems.list</code> method to retrieve a list of videos uploaded
to the channel associated with the request. The code also calls the <code>channels.list</code> method with the
<code>mine</code> parameter set to <code>true</code> to retrieve the playlist ID that identifies the channel's uploaded
videos.

### [Search by keyword](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/Search.java)

Method: youtube.search.list<br>
Description: This sample calls the API's <code>search.list</code> method to retrieve search results associated with
a particular keyword.

### [Search by location](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/GeolocationSearch.java)

Method: youtube.search.list, youtube.videos.list<br>
Description: This sample calls the API's <code>search.list</code> method with the <code>type</code>, <code>q</code>, <code>location</code>, and
<code>locationRadius</code> parameters to retrieve search results matching the provided keyword within the radius centered
at a particular location. Using the video IDs from the search result, the sample calls the API's <code>videos.list</code>
method to retrieve location details of each video.

### [Set and retrieve localized channel metadata](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/ChannelLocalizations.java)

Method: youtube.channels.update, youtube.channels.list<br>
Description: This sample demonstrates how to use the following API methods to set and retrieve localized metadata for a
channel:<br>
<ul>
<li>It calls the <code>channels.update</code> method to update the default language of a channel's metadata and to add a
localized version of this metadata in a selected language. Note that to set the default language for a channel resource,
you actually need to update the <code>brandingSettings.channel.defaultLanguage</code> property.</li>
<li>It calls the <code>channels.list</code> method with the <code>hl</code> parameter set to a specific language to
retrieve localized metadata in that language.</li>
<li>It calls the <code>channels.list</code> method and includes <code>localizations</code> in the <code>part</code>
parameter value to retrieve all of the localized metadata for that channel.</li>
</ul>

### [Set and retrieve localized channel section metadata](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/ChannelSectionLocalizations.java)

Method: youtube.channelSections.update, youtube.channelSections.list<br>
Description: This sample demonstrates how to use the following API methods to set and retrieve localized metadata for a
channel section:<br>
<ul>
<li>It calls the <code>channelSections.update</code> method to update the default language of a channel section's
metadata and to add a localized version of this metadata in a selected language.</li>
<li>It calls the <code>channelSections.list</code> method with the <code>hl</code> parameter set to a specific language
to retrieve localized metadata in that language.</li>
<li>It calls the <code>channelSections.list</code> method and includes <code>localizations</code> in the
<code>part</code> parameter value to retrieve all of the localized metadata for that channel section.</li>
</ul>

### [Set and retrieve localized playlist metadata](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/PlaylistLocalizations.java)

Method: youtube.playlists.update, youtube.playlists.list<br>
Description: This sample demonstrates how to use the following API methods to set and retrieve localized metadata for a
playlist:<br>
<ul>
<li>It calls the <code>playlists.update</code> method to update the default language of a playlist's metadata and to add
a localized version of this metadata in a selected language.</li>
<li>It calls the <code>playlists.list</code> method with the <code>hl</code> parameter set to a specific language to
retrieve localized metadata in that language.</li>
<li>It calls the <code>playlists.list</code> method and includes <code>localizations</code> in the <code>part</code>
parameter value to retrieve all of the localized metadata for that playlist.</li>
</ul>

### [Set and retrieve localized video metadata](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/VideoLocalizations.java)

Method: youtube.videos.update, youtube.videos.list<br>
Description: This sample demonstrates how to use the following API methods to set and retrieve localized metadata
for a video:<br>
<ul>
<li>It calls the <code>videos.update</code> method to update the default language of a video's metadata and to add
a localized version of this metadata in a selected language.</li>
<li>It calls the <code>videos.list</code> method with the <code>hl</code> parameter set to a specific language to
retrieve localized metadata in that language.</li>
<li>It calls the <code>videos.list</code> method and includes <code>localizations</code> in the <code>part</code>
parameter value to retrieve all of the localized metadata for that video.</li>
</ul>

### [Update a video](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/UpdateVideo.java)

Method: youtube.videos.update<br>
Description: This sample calls the API's <code>videos.update</code> method to update a video owned by the channel
authorizing the request.

### [Upload a custom video thumbnail image](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/UploadThumbnail.java)

Method: youtube.thumbnails.set<br>
Description: This sample calls the API's <code>thumbnails.set</code> method to upload an image and set it as the
thumbnail image for a video. The request must be authorized by the channel that owns the video.

### [Upload a video](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/data/UploadVideo.java)

Method: youtube.videos.insert<br>
Description: This sample calls the API's <code>videos.insert</code> method to upload a video to the channel associated
with the request.

### [Retrieve top 10 videos by viewcount](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/analytics/YouTubeAnalyticsReports.java)

Method: youtubeAnalytics.reports.query<br>
Description: This sample calls the API's <code>reports.query</code> method to retrieve YouTube Analytics data.
By default, the report retrieves the top 10 videos based on viewcounts, and it returns several metrics for those
videos, sorting the results in reverse order by viewcount. By setting command line parameters, you can use the
same code to retrieve other reports as well.

### [Create a reporting job](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/reporting/CreateReportingJob.java)

Method: youtubeReporting.reportTypes.list, youtubeReporting.jobs.create<br>
Description: This sample demonstrates how to create a reporting job. It calls the <code>reportTypes.list</code> method
to retrieve a list of available report types. It then calls the <code>jobs.create</code> method to create a new reporting
job.

### [Retrieve reports](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/reporting/RetrieveReports.java)

Method: youtubeReporting.jobs.list, youtubeReporting.reports.list<br>
Description: This sample demonstrates how to retrieve reports created by a specific job. It calls the
<code>jobs.list</code> method to retrieve reporting jobs. It then calls the <code>reports.list</code> method with the
<code>jobId</code> parameter set to a specific job ID to retrieve reports created by that job. Finally, the sample
prints out the download URL for each report.

### [Create a broadcast and stream](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/live/CreateBroadcast.java)

Method: youtube.liveBroadcasts.bind,youtube.liveBroadcasts.insert,youtube.liveStreams.insert<br>
Description: This sample calls the API's <code>liveBroadcasts.insert</code> and <code>liveStreams.insert</code>
methods to create a broadcast and a stream. Then, it calls the <code>liveBroadcasts.bind</code> method to bind
the stream to the broadcast.

### [Retrieve a channel's broadcasts](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/live/ListBroadcasts.java)

Method: youtube.liveBroadcasts.list<br>
Description: This sample calls the API's <code>liveBroadcasts.list</code> method to retrieve a list of broadcasts for
the channel associated with the request. By default, the request retrieves all broadcasts for the channel, but you can
also specify a value for the <code>--broadcast-status</code> option to only retrieve broadcasts with a particular status.

### [Retrieve a channel's live video streams](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/live/ListStreams.java)

Method: youtube.liveStreams.list<br>
Description: This sample calls the API's <code>liveStreams.list</code> method to retrieve a list of video stream settings
that a channel can use to broadcast live events on YouTube.


### [Get a live chat id](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/live/GetLiveChatId.java)

Methods: youtube.videos.list, youtube.liveBroadcasts.list<br>
Description: This sample retrieves the live chat ID from either a <code>videoId</code> parameter
or the live broadcast for the authorized user's channel. The <code>liveChatId</code> is required for other samples
that interact with live chat.

### [Insert a live chat message](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/live/InsertLiveChatMessage.java)

Method: youtube.liveChatMessages.insert<br>
Description: This sample inserts a live chat message into the the specified video or the live broadcast for
the authorized user's channel.

### [Delete a live chat message](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/live/DeleteLiveChatMessage.java)

Method: youtube.liveChatMessages.delete<br>
Description: This sample deletes the specified live chat message.

### [List live chat messages](/java/src/main/java/com/google/api/services/samples/youtube/cmdline/live/ListLiveChatMessages.java)

Method: youtube.liveChatMessages.list<br>
Description: This sample lists live chat messages from the specified video or from the live broadcast for
the authorized user's channel.
