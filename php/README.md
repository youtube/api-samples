## Samples in this directory:

### [Add a channel section](/php/add_channel_section.php)

Method: youtube.channelSections.insert<br>
Description: This sample calls the API's <code>channelSections.insert</code> method to create channel sections.
The code accepts a number of command line arguments that let you specify the section's type, display style, title, position,
and content.<br><br>
This sample also updates the channel's
<code><a href="/youtube/v3/docs/channels#brandingSettings.channel.showBrowseView">brandingSettings.channel.showBrowseView</a></code>
property so that the channel displays content in a browse view (rather than a feed view). A channel's sections are only
visible if the channel displays content in a browse view.<br><br>More information on channel sections is available in the
<a href="https://support.google.com/youtube/answer/3027787">YouTube Help Center</a>.

### [Add a channel subscription](/php/add_subscription.php)

Method: youtube.subscriptions.insert<br>
Description: This sample calls the API's <code>subscriptions.insert</code> method to add a subscription to a specified
channel.

### [Create a playlist](/php/playlist_updates.php)

Method: youtube.playlists.insert<br>
Description: This sample calls the API's <code>playlists.insert</code> method to create a private playlist owned by the
channel authorizing the request.

### [Create and manage comments](/php/comment_handling.php)

Method: youtube.commentThreads.list, youtube.comments.insert, youtube.comments.list, youtube.comments.update,
youtube.comments.setModerationStatus, youtube.comments.markAsSpam, youtube.comments.delete<br>
Description: The following code sample demonstrates how to use the following API methods to create and manage comments:<br>
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
delete the comment, using the <code>id</code> parameter to identify the comment.</li>
</ul>

### [Create and manage comment threads](/php/comment_threads.php)

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

### [Create and manage YouTube video caption tracks](/php/captions.php)

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

### [Retrieve my uploads](/php/my_uploads.php)

Method: youtube.playlistItems.list<br>
Description: This sample calls the API's <code>playlistItems.list</code> method to retrieve a list of videos uploaded
to the channel associated with the request. The code also calls the <code>channels.list</code> method with the
<code>mine</code> parameter set to <code>true</code> to retrieve the playlist ID that identifies the channel's uploaded
videos.

### [Search by keyword](/php/search.php)

Method: youtube.search.list<br>
Description: This sample calls the API's <code>search.list</code> method to retrieve search results associated with
a particular keyword.

### [Search by location](/php/geolocation_search.php)

Method: youtube.search.list, youtube.videos.list<br>
Description: This sample calls the API's <code>search.list</code> method with the <code>type</code>, <code>q</code>,
<code>location</code> and <code>locationRadius</code> parameters to retrieve search results matching the provided
keyword within the radius centered at a particular location. Using the video IDs from the search result, the sample
calls the API's <code>videos.list</code> method to retrieve location details of each video.

### [Set and retrieve localized channel metadata](/php/channel_localizations.php)

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

### [Set and retrieve localized channel section metadata](/php/channel_section_localizations.php)

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

### [Set and retrieve localized playlist metadata](/php/playlist_localizations.php)

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

### [Set and retrieve localized video metadata](/php/video_localizations.php)

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

### [Shuffle existing channel sections](/php/shuffle_channel_sections.php)

Method: youtube.channelSections.list,youtube.channelSections.update<br>
Description: This sample calls the API's <code>channelSections.list</code> method to get the list of current channel
sections, shuffles them, and then calls <code>channelSections.update</code> to change the position of each.<br><br>
More information on channel sections is available in the
<a href="https://support.google.com/youtube/answer/3027787">YouTube Help Center</a>.

### [Update a video](/php/update_video.php)

Method: youtube.videos.update<br>
Description: This code sample demonstrates how to add tags into an existing video.<br><br>The following code
sample calls the API's <code>youtube.videos.list</code> method with <code>id</code> parameter set to videoId
to get the video object. Using this video object, the sample gets the list of tags and appends new tags at the
end of this list.  Finally, the code calls <code>youtube.videos.update</code> method with updated video object
to persist these changes on YouTube.

### [Upload a banner image and set as channel's banner](/php/upload_banner.php)

Method: youtube.channelBanners.insert, youtube.channels.update<br>
Description: This sample calls the API's <code>channelBanners.insert</code> method to upload an image. With the
returned URL, the sample calls <code>channels.update</code> method to update the channel's banner to this image.

### [Upload a custom video thumbnail image](/php/upload_thumbnail.php)

Method: youtube.thumbnails.set<br>
Description: This sample demonstrates how to upload a custom video thumbnail to YouTube and set it for a video.
It calls the API's <code>youtube.thumbnails.set</code> method with <code>videoId</code> parameter set to a video
ID to use a custom image as a thumbnail to the video. For the image upload, the program utilizes the
<code>Google_MediaFileUpload</code> class with the <code>resumable</code> parameter set to
<code>true</code> to upload the image piece-by-piece, allowing for subsequent retries to resume uploading from
a point close to where the previous retry failed, a feature useful for programs that need to upload large files.

### [Upload a video](/php/resumable_upload.php)

Method: youtube.videos.insert<br>
Description: The following code sample calls the API's <code>videos.insert</code> method to add a video to user's
channel. The code also utilizes <code>Google_MediaFileUpload</code> class with the <code>resumable upload</code>
parameter set to <code>true</code> to be able to to upload the video in chunks.

### [Create a broadcast and stream](/php/create_broadcast.php)

Method: youtube.liveBroadcasts.bind,youtube.liveBroadcasts.insert,youtube.liveStreams.insert<br>
Description: This sample calls the API's <code>liveBroadcasts.insert</code> and <code>liveStreams.insert</code>
methods to create a broadcast and a stream. Then, it calls the <code>liveBroadcasts.bind</code> method to bind
the stream to the broadcast.

### [Retrieve a channel's broadcasts](/php/list_broadcasts.php)

Method: youtube.liveBroadcasts.list<br>
Description: This sample calls the API's <code>liveBroadcasts.list</code> method to retrieve a list of broadcasts for
the channel associated with the request. By default, the request retrieves all broadcasts for the channel, but you can
also specify a value for the <code>--broadcast-status</code> option to only retrieve broadcasts with a particular status.

### [Retrieve a channel's live video streams](/php/list_streams.php)

Method: youtube.liveStreams.list<br>
Description: This sample calls the API's <code>liveStreams.list</code> method to retrieve a list of video stream settings
that a channel can use to broadcast live events on YouTube.

### [Create a reporting job](/php/create_reporting_job.php)

Method: youtubeReporting.reportTypes.list, youtubeReporting.jobs.create<br>
Description: This sample demonstrates how to create a reporting job. It calls the <code>reportTypes.list</code> method
to retrieve a list of available report types. It then calls the <code>jobs.create</code> method to create a new reporting
job.

### [Retrieve reports](/php/retrieve_reports.php)

Method: youtubeReporting.jobs.list, youtubeReporting.reports.list<br>
Description: This sample demonstrates how to retrieve reports created by a specific job. It calls the
<code>jobs.list</code> method to retrieve reporting jobs. It then calls the <code>reports.list</code> method with the
<code>jobId</code> parameter set to a specific job id to retrieve reports created by that job. Finally, the sample
prints out the download URL for each report.
