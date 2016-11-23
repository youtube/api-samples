## Samples in this directory:

### [Add a channel section](/youtube/api-samples/blob/master/python/add_channel_section.py)

Method: youtube.channelSections.insert<br>
Description: This sample calls the API's <code>channelSections.insert</code> method to create channel sections.
The code accepts a number of command line arguments that let you specify the section's type, display style, title, position,
and content.<br><br>
This sample also updates the channel's
<code><a href="/youtube/v3/docs/channels#brandingSettings.channel.showBrowseView">brandingSettings.channel.showBrowseView</a></code>
property so that the channel displays content in a browse view (rather than a feed view). A channel's sections are only
visible if the channel displays content in a browse view.</p><p>More information on channel sections is available in the
<a href="https://support.google.com/youtube/answer/3027787">YouTube Help Center</a>.

### [Add a featured video](/youtube/api-samples/blob/master/python/add_featured_video.py)

Method: youtube.channels.update<br>
Description: This sample calls the API's <code>channels.update</code> method to set <code>invideoPromotion</code>
properties for the channel.

### [Add a channel subscription](/youtube/api-samples/blob/master/python/add_subscription.py)

Method: youtube.subscriptions.insert<br>
Description: This sample calls the API's <code>subscriptions.insert</code> method to add a subscription to a specified
channel.

### [Create and manage YouTube video caption tracks](/youtube/api-samples/blob/master/python/captions.py)

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

### [Post a channel bulletin](/youtube/api-samples/blob/master/python/channel_bulletin.py)

Method: youtube.activities.insert<br>
Description: This sample calls the API's <code>activities.insert</code> method to post a bulletin to the channel
associated with the request.

### [Set and retrieve localized channel metadata](/youtube/api-samples/blob/master/python/channel_localizations.py)

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

### [Set and retrieve localized channel section metadata](/youtube/api-samples/blob/master/python/channel_section_localizations.py)

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

### [Create and manage comments](/youtube/api-samples/blob/master/python/comment_handling.py)

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

### [Create and manage comment threads](/youtube/api-samples/blob/master/python/comment_threads.py)

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

### [Create a broadcast](/youtube/api-samples/blob/master/python/create_broadcast.py)

Method: youtube.liveBroadcasts.bind,youtube.liveBroadcasts.insert,youtube.liveStreams.insert<br>
Description: This sample calls the API's <code>liveBroadcasts.insert</code> method to create a broadcast.

### [Create a reporting job](/youtube/api-samples/blob/master/python/create_reporting_job.py)

Method: youtubeReporting.reportTypes.list, youtubeReporting.jobs.create<br>
Description: This sample demonstrates how to create a reporting job. It calls the <code>reportTypes.list</code> method
to retrieve a list of available report types. It then calls the <code>jobs.create</code> method to create a new reporting
job.

### [Search by geolocation](/youtube/api-samples/blob/master/python/geolocation_search.py)

Method: youtube.search.list, youtube.videos.list<br>
Description: This sample calls the API's <code>search.list</code> method with the <code>type</code>,
<code>q</code>, <code>location</code>, and <code>locationRadius</code> parameters to retrieve search results
matching the provided keyword within the radius centered at a particular location. Using the video ids from
the search result, the sample calls the API's <code>videos.list</code> method to retrieve location details
of each video.

### [Like a video](/youtube/api-samples/blob/master/python/like_video.py)

Method: youtube.videos.rate<br>
Description: This sample calls the API's <code>videos.rate</code> method to set a positive rating for a video.

### [Retrieve a channel's broadcasts](/youtube/api-samples/blob/master/python/list_broadcasts.py)

Method: youtube.liveBroadcasts.list<br>
Description: This sample calls the API's <code>liveBroadcasts.list</code> method to retrieve a list of broadcasts for
the channel associated with the request. By default, the request retrieves all broadcasts for the channel, but you can
also specify a value for the <code>--broadcast-status</code> option to only retrieve broadcasts with a particular status.

### [Retrieve a channel's live video streams](/youtube/api-samples/blob/master/python/list_streams.py)

Method: youtube.liveStreams.list<br>
Description: This sample calls the API's <code>liveStreams.list</code> method to retrieve a list of video stream settings
that a channel can use to broadcast live events on YouTube.

### [Retrieve my uploads](/youtube/api-samples/blob/master/python/my_uploads.py)

Method: youtube.playlistItems.list<br>
Description: This sample calls the API's <code>playlistItems.list</code> method to retrieve a list of videos uploaded
to the channel associated with the request. The code also calls the <code>channels.list</code> method with the
<code>mine</code> parameter set to <code>true</code> to retrieve the playlist ID that identifies the channel's uploaded
videos.

### [Set and retrieve localized playlist metadata](/youtube/api-samples/blob/master/python/playlist_localizations.py)

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

### [Create a playlist](/youtube/api-samples/blob/master/python/playlist_updates.py)

Method: youtube.playlists.insert<br>
Description: This sample calls the API's <code>playlists.insert</code> method to create a private playlist owned by the
channel authorizing the request.

### [Retrieve reports](/youtube/api-samples/blob/master/python/retrieve_reports.py)

Method: youtubeReporting.jobs.list, youtubeReporting.reports.list<br>
Description: This sample demonstrates how to retrieve reports created by a specific job. It calls the
<code>jobs.list</code> method to retrieve reporting jobs. It then calls the <code>reports.list</code> method with the
<code>jobId</code> parameter set to a specific job id to retrieve reports created by that job. Finally, the sample
prints out the download URL for each report.

### [Search by keyword](/youtube/api-samples/blob/master/python/search.py)

Method: youtube.search.list<br>
Description: This sample calls the API's <code>search.list</code> method to retrieve search results associated with
a particular keyword.

### [Upload a watermark image and set it for a channel](/youtube/api-samples/blob/master/python/set_watermark.py)

Method: youtube.watermarks.set<br>
Description: This sample calls the API's <code>watermarks.set</code> method to upload an image and set it as the
watermark image for a channel. The request must be authorized by the channel that owns the video.

### [Shuffle existing channel sections](/youtube/api-samples/blob/master/python/shuffle_channel_sections.py)

Method: youtube.channelSections.list,youtube.channelSections.update<br>
Description: This sample calls the API's <code>channelSections.list</code> method to get the list of current channel
sections. Then it shuffles the list and calls <code>channelSections.update</code> to change the position of each item.<br><br>
More information on channel sections is available in the
<a href="https://support.google.com/youtube/answer/3027787">YouTube Help Center</a>.

### [Remove a watermark image from a channel](/youtube/api-samples/blob/master/python/unset_watermark.py)

Method: youtube.watermarks.unset<br>
Description: This sample calls the API's <code>watermarks.unset</code> method to remove the watermark
image for a channel. The request must be authorized by the channel that owns the video.

### [Update a video](/youtube/api-samples/blob/master/python/update_video.py)

Method: youtube.videos.update<br>
Description: This sample calls the API's <code>videos.update</code> method to update a video owned by the channel
authorizing the request.

### [Upload a banner image and set as channel's banner](/youtube/api-samples/blob/master/python/upload_banner.py)

Method: youtube.channelBanners.insert, youtube.channels.update<br>
Description: This sample calls the API's <code>channelBanners.insert</code> method to upload an image. With the
returned URL, the sample calls <code>channels.update</code> method to update the channel's banner to that image.

### [Upload a video thumbnail image](/youtube/api-samples/blob/master/python/upload_thumbnail.py)

Method: youtube.thumbnails.set<br>
Description: This sample calls the API's <code>thumbnails.set</code> method to upload an image and set it as the
thumbnail image for a video. The request must be authorized by the channel that owns the video.

### [Upload a video](/youtube/api-samples/blob/master/python/upload_video.py)

Method: youtube.videos.insert<br>
Description: This sample calls the API's <code>videos.insert</code> method to upload a video to the channel associated
with the request.

### [Set and retrieve localized video metadata](/youtube/api-samples/blob/master/python/video_localizations.py)

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

### [Retrieve top 10 videos by viewcount](/youtube/api-samples/blob/master/python/yt_analytics_report.py)

Method: youtubeAnalytics.reports.query<br>
Description: This sample calls the API's <code>reports.query</code> method to retrieve YouTube Analytics data.
By default, the report retrieves the top 10 videos based on viewcounts, and it returns several metrics for those
videos, sorting the results in reverse order by viewcount. By setting command line parameters, you can use the
same code to retrieve other reports as well.
