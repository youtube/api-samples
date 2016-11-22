## Samples in this directory:

### [Add a channel subscription](/youtube/api-samples/blob/master/ruby/add_subscription.rb)

Method: youtube.subscriptions.insert<br>
Description: This sample calls the API's <code>subscriptions.insert</code> method to add a subscription
to a specified channel.

### [Post a channel bulletin](/youtube/api-samples/blob/master/ruby/channel_bulletin.rb)

Method: youtube.activities.insert<br>
Description: This sample calls the API's <code>activities.insert</code> method to post a bulletin to the channel
associated with the request.

### [Upload a video](/youtube/api-samples/blob/master/ruby/upload_video.rb)

Method: youtube.videos.insert<br>
Description: This sample calls the API's <code>videos.insert</code> method to upload a video to the channel
associated with the request.

### [Retrieve my uploads](/youtube/api-samples/blob/master/ruby/my_uploads.rb)

Method: youtube.playlistItems.list<br>
Description: This sample calls the API's <code>playlistItems.list</code> method to retrieve a list of videos uploaded
to the channel associated with the request. The code also calls the <code>channels.list</code> method with the
<code>mine</code> parameter set to <code>true</code> to retrieve the playlist ID that identifies the channel's
uploaded videos.

### [Search by keyword](/youtube/api-samples/blob/master/ruby/search.rb)

Method: youtube.search.list<br>
Description: This sample calls the API's <code>search.list</code> method to retrieve search results
associated with a particular keyword.

### [Retrieve top 10 videos by viewcount](/youtube/api-samples/blob/master/ruby/yt_analytics_report.rb)

Method: youtube.playlists.insert<br>
Description: This sample calls the API's <code>reports.query</code> method to retrieve YouTube Analytics data.
By default, the report retrieves the top 10 videos based on viewcounts, and it returns several metrics for those
videos, sorting the results in reverse order by viewcount. By setting command line parameters, you can use the same
code to retrieve other reports as well.

### [Authorize a request](/youtube/api-samples/blob/master/ruby/oauth/oauth_util.rb)

Description: The following code sample performs OAuth 2.0 authorization by checking for the presence of a local
file that contains authorization credentials. If the file is not present, the script opens a browser and waits
for a response, then saves the returned credentials locally.
