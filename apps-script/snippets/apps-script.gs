// Sample Apps Script code for printing API response data
function printResults(response) {
  var props = ['type', 'title', 'textDisplay', 'channelId', 'videoId', 'hl', 'gl', 'label'];
  for (var r = 0; r < response['items'].length; r++) {
    var item = response['items'][r];
    var itemId = '';
    var value;
    if (item['rating']) {
      itemId = item['id'];
      value = 'Rating: ' + item['rating'];
    } else {
      if (item['id']['videoId']) {
        itemId = item['id']['videoId'];
      } else if (item['id']['channelId']) {
        itemId = item['id']['channelId'];
      } else {
        itemId = item['id'];
      }
      
      for (var p = 0; p < props.length; p++) {
        if (item['snippet'][props[p]]) {
          value = itemId + ': ' + item['snippet'][props[p]];
          break;
        }
      }
    }
    Logger.log(value);
  }
}

/**
 * This example retrieves the 25 most recent activities for the Google Developers 
 * channel. It retrieves the snippet and contentDetails parts for each activity 
 * resource. 
 */
function activitiesList(part, channelId, maxResults) {
  var response = YouTube.Activities.list(part,
      {'channelId': channelId,
       'maxResults': maxResults});
  printResults(response);
}

/**
 * This example retrieves the 25 most recent activities performed by the user 
 * authorizing the API request. 
 */
function activitiesListMine(part, maxResults, mine) {
  var response = YouTube.Activities.list(part,
      {'maxResults': maxResults,
       'mine': mine});
  printResults(response);
}

/**
 * This example lists caption tracks available for the Volvo Trucks "Epic Split" 
 * commercial, featuring Jean-Claude Van Damme. (This video was selected because 
 * it has many available caption tracks and also because it is awesome.) 
 */
function captionsList(part, videoId) {
  var response = YouTube.Captions.list(part, videoId);
  printResults(response);
}

/**
 * This example retrieves channel data for the GoogleDevelopers YouTube channel. 
 * It uses the id request parameter to identify the channel by its YouTube channel 
 * ID. 
 */
function channelsListById(part, id) {
  var response = YouTube.Channels.list(part,
      {'id': id});
  printResults(response);
}

/**
 * This example retrieves channel data for the GoogleDevelopers YouTube channel. 
 * It uses the forUsername request parameter to identify the channel by its 
 * YouTube username. 
 */
function channelsListByUsername(part, forUsername) {
  var response = YouTube.Channels.list(part,
      {'forUsername': forUsername});
  printResults(response);
}

/**
 * This example retrieves the channel data for the authorized user's YouTube 
 * channel. It uses the mine request parameter to indicate that the API should 
 * only return channels owned by the user authorizing the request. 
 */
function channelsListMine(part, mine) {
  var response = YouTube.Channels.list(part,
      {'mine': mine});
  printResults(response);
}

/**
 * This example retrieves the channel sections shown on the Google Developers 
 * channel, using the channelId request parameter to identify the channel. 
 */
function channelSectionsListById(part, channelId) {
  var response = YouTube.ChannelSections.list(part,
      {'channelId': channelId});
  printResults(response);
}

/**
 * This example retrieves the channel sections shown on the authorized user's 
 * channel. It uses the mine request parameter to indicate that the API should 
 * return channel sections on that channel. 
 */
function channelSectionsListMine(part, mine) {
  var response = YouTube.ChannelSections.list(part,
      {'mine': mine});
  printResults(response);
}

/**
 * This example retrieves comment replies for a specified comment, which is 
 * identified by the parentId request parameter. In this example, the parent 
 * comment is the first comment on a video about Apps Script. The video was chosen 
 * because this particular comment had multiple replies (in multiple languages) 
 * and also because Apps Script is really useful. 
 */
function commentsList(part, parentId) {
  var response = YouTube.Comments.list(part,
      {'parentId': parentId});
  printResults(response);
}

/**
 * This example retrieves all comment threads associated with a particular 
 * channel. The response could include comments about the channel or about the 
 * channel's videos. The request's allThreadsRelatedToChannelId parameter 
 * identifies the channel. 
 */
function commentThreadsListAllThreadsByChannelId(part, allThreadsRelatedToChannelId) {
  var response = YouTube.CommentThreads.list(part,
      {'allThreadsRelatedToChannelId': allThreadsRelatedToChannelId});
  printResults(response);
}

/**
 * This example retrieves all comment threads about the specified channel. The 
 * request's channelId parameter identifies the channel. The response does not 
 * include comments left on videos that the channel uploaded. 
 */
function commentThreadsListByChannelId(part, channelId) {
  var response = YouTube.CommentThreads.list(part,
      {'channelId': channelId});
  printResults(response);
}

/**
 * This example retrieves all comment threads associated with a particular video. 
 * The request's videoId parameter identifies the video. 
 */
function commentThreadsListByVideoId(part, videoId) {
  var response = YouTube.CommentThreads.list(part,
      {'videoId': videoId});
  printResults(response);
}

/**
 * This example retrieves a list of application languages that the YouTube website 
 * supports. The example sets the hlparameter value to es_MX, indicating that text 
 * values in the API response should be provided in that language. That 
 * parameter's default value is en_US. 
 */
function i18nLanguagesList(part, hl) {
  var response = YouTube.I18nLanguages.list(part,
      {'hl': hl});
  printResults(response);
}

/**
 * This example retrieves a list of content regions that the YouTube website 
 * supports. The example sets the hlparameter value to es_MX, indicating that text 
 * values in the API response should be provided in that language. That 
 * parameter's default value is en_US. 
 */
function i18nRegionsList(part, hl) {
  var response = YouTube.I18nRegions.list(part,
      {'hl': hl});
  printResults(response);
}

/**
 * This example retrieves the list of videos in a specified playlist. The 
 * request's playlistId parameter identifies the playlist.

Note that the API 
 * response does not include metadata about the playlist itself, such as the 
 * playlist's title and description. Additional metadata about the videos in the 
 * playlist can also be retrieved using the videos.listmethod. 
 */
function playlistItemsListByPlaylistId(part, maxResults, playlistId) {
  var response = YouTube.PlaylistItems.list(part,
      {'maxResults': maxResults,
       'playlistId': playlistId});
  printResults(response);
}

/**
 * This example retrieves playlists owned by the YouTube channel that the 
 * request's channelId parameter identifies. 
 */
function playlistsListByChannelId(part, channelId, maxResults) {
  var response = YouTube.Playlists.list(part,
      {'channelId': channelId,
       'maxResults': maxResults});
  printResults(response);
}

/**
 * This example retrieves playlists created in the authorized user's YouTube 
 * channel. It uses the mine request parameter to indicate that the API should 
 * only return playlists owned by the user authorizing the request. 
 */
function playlistsListMine(part, mine) {
  var response = YouTube.Playlists.list(part,
      {'mine': mine});
  printResults(response);
}

/**
 * This example retrieves the first 25 search results associated with the keyword 
 * surfing. Since the request doesn't specify a value for the type request 
 * parameter, the response can include videos, playlists, and channels. 
 */
function searchListByKeyword(part, maxResults, q, type) {
  var response = YouTube.Search.list(part,
      {'maxResults': maxResults,
       'q': q,
       'type': type});
  printResults(response);
}

/**
 * This example retrieves search results associated with the keyword surfing that 
 * also specify in their metadata a geographic location within 10 miles of the 
 * point identified by the location parameter value. (The sample request specifies 
 * a point on the North Shore of Oahu, Hawaii . The request retrieves the top five 
 * results, which is the default number returned when the maxResults parameter is 
 * not specified. 
 */
function searchListByLocation(part, location, locationRadius, q, type) {
  var response = YouTube.Search.list(part,
      {'location': location,
       'locationRadius': locationRadius,
       'q': q,
       'type': type});
  printResults(response);
}

/**
 * This example retrieves a list of acdtive live broadcasts (see the eventType 
 * parameter value) that are associated with the keyword news. Since the eventType 
 * parameter is set, the request must also set the type parameter value to video. 
 */
function searchListLiveEvents(part, eventType, maxResults, q, type) {
  var response = YouTube.Search.list(part,
      {'eventType': eventType,
       'maxResults': maxResults,
       'q': q,
       'type': type});
  printResults(response);
}

/**
 * This example searches within the authorized user's videos for videos that match 
 * the keyword fun. The forMine parameter indicates that the response should only 
 * search within the authorized user's videos. Also, since this request uses the 
 * forMine parameter, it must also set the type parameter value to video.

If you 
 * have not uploaded any videos associated with that term, you will not see any 
 * items in the API response list. 
 */
function searchListMine(part, maxResults, forMine, q, type) {
  var response = YouTube.Search.list(part,
      {'maxResults': maxResults,
       'forMine': forMine,
       'q': q,
       'type': type});
  printResults(response);
}

/**
 * This example sets the relatedToVideoId parameter to retrieve a list of videos 
 * related to that video. Since the relatedToVideoId parameter is set, the request 
 * must also set the type parameter value to video. 
 */
function searchListRelatedVideos(part, relatedToVideoId, type) {
  var response = YouTube.Search.list(part,
      {'relatedToVideoId': relatedToVideoId,
       'type': type});
  printResults(response);
}

/**
 * This example retrieves a list of channels that the specified channel subscribes 
 * to. In this example, the API response lists channels to which the GoogleDevelopers channel 
 * subscribes. 
 */
function subscriptionsListByChannelId(part, channelId) {
  var response = YouTube.Subscriptions.list(part,
      {'channelId': channelId});
  printResults(response);
}

/**
 * This example determines whether the user authorizing the API request subscribes 
 * to the channel that the forChannelId parameter identifies. To check whether 
 * another channel (instead of the authorizing user's channel) subscribes to the 
 * specified channel, remove the mine parameter from this request and add the channelId parameter 
 * instead.

In this example, the API response contains one item if you subscribe to 
 * the GoogleDevelopers channel. Otherwise, the request does not return any items. 
 */
function subscriptionsListForChannelId(part, forChannelId, mine) {
  var response = YouTube.Subscriptions.list(part,
      {'forChannelId': forChannelId,
       'mine': mine});
  printResults(response);
}

/**
 * This example uses the mySubscribers parameter to retrieve the list of channels 
 * to which the authorized user subscribes. 
 */
function subscriptionsListMySubscribers(part, mySubscribers) {
  var response = YouTube.Subscriptions.list(part,
      {'mySubscribers': mySubscribers});
  printResults(response);
}

/**
 * This example uses the mine parameter to retrieve a list of channels that 
 * subscribe to the authenticated user's channel. 
 */
function subscriptionsListMySubscriptions(part, mine) {
  var response = YouTube.Subscriptions.list(part,
      {'mine': mine});
  printResults(response);
}

/**
 * This example shows how to retrieve a list of reasons that can be used to report 
 * abusive videos. You can retrieve the text labels in other languages by 
 * specifying a value for the hl request parameter. 
 */
function videoAbuseReportReasonsList(part) {
  var response = YouTube.VideoAbuseReportReasons.list(part);
  printResults(response);
}

/**
 * This example retrieves a list of categories that can be associated with YouTube 
 * videos in the United States. The regionCode parameter specifies the country for 
 * which categories are being retrieved. 
 */
function videoCategoriesList(part, regionCode) {
  var response = YouTube.VideoCategories.list(part,
      {'regionCode': regionCode});
  printResults(response);
}

/**
 * This example uses the regionCode to retrieve a list of categories that can be 
 * associated with YouTube videos in Spain. It also uses the hl parameter to 
 * indicate that text labels in the response should be specified in Spanish. 
 */
function videoCategoriesListForRegion(part, hl, regionCode) {
  var response = YouTube.VideoCategories.list(part,
      {'hl': hl,
       'regionCode': regionCode});
  printResults(response);
}

/**
 * This example retrieves information about a specific video. It uses the id 
 * parameter to identify the video. 
 */
function videosListById(part, id) {
  var response = YouTube.Videos.list(part,
      {'id': id});
  printResults(response);
}

/**
 * This example retrieves a list of YouTube's most popular videos. The regionCode 
 * parameter identifies the country for which you are retrieving videos. The 
 * sample code is set to default to return the most popular videos in the United 
 * States. You could also use the videoCategoryId parameter to retrieve the most 
 * popular videos in a particular category. 
 */
function videosListMostPopular(part, chart, regionCode, videoCategoryId) {
  var response = YouTube.Videos.list(part,
      {'chart': chart,
       'regionCode': regionCode,
       'videoCategoryId': videoCategoryId});
  printResults(response);
}

/**
 * This example retrieves information about a group of videos. The id parameter 
 * value is a comma-separated list of YouTube video IDs. You might issue a request 
 * like this to retrieve additional information about the items in a playlist or 
 * the results of a search query. 
 */
function videosListMultipleIds(part, id) {
  var response = YouTube.Videos.list(part,
      {'id': id});
  printResults(response);
}

/**
 * This example retrieves a list of videos liked by the user authorizing the API 
 * request. By setting the rating parameter value to dislike, you could also use 
 * this code to retrieve disliked videos. 
 */
function videosListMyRatedVideos(part, myRating) {
  var response = YouTube.Videos.list(part,
      {'myRating': myRating});
  printResults(response);
}

/**
 * This example retrieves the rating that the user authorizing the request gave to 
 * a particular video. In this example, the video is of Amy Cuddy's TED talk about 
 * body language. 
 */
function videosGetRating(id) {
  var response = YouTube.Videos.getRating(id);
  printResults(response);
}

function Main() {
  activitiesList('snippet,contentDetails', 'UC_x5XG1OV2P6uZZ5FSM9Ttw', 25);
  activitiesListMine('snippet,contentDetails', 25, true);
  captionsList('snippet', 'M7FIvfx5J10');
  channelsListById('snippet,contentDetails,statistics', 'UC_x5XG1OV2P6uZZ5FSM9Ttw');
  channelsListByUsername('snippet,contentDetails,statistics', 'GoogleDevelopers');
  channelsListMine('snippet,contentDetails,statistics', true);
  channelSectionsListById('snippet,contentDetails', 'UC_x5XG1OV2P6uZZ5FSM9Ttw');
  channelSectionsListMine('snippet,contentDetails', true);
  commentsList('snippet', 'z13icrq45mzjfvkpv04ce54gbnjgvroojf0');
  commentThreadsListAllThreadsByChannelId('snippet,replies', 'UC_x5XG1OV2P6uZZ5FSM9Ttw');
  commentThreadsListByChannelId('snippet,replies', 'UCAuUUnT6oDeKwE6v1NGQxug');
  commentThreadsListByVideoId('snippet,replies', 'm4Jtj2lCMAA');
  i18nLanguagesList('snippet', 'es_MX');
  i18nRegionsList('snippet', 'es_MX');
  playlistItemsListByPlaylistId('snippet,contentDetails', 25, 'PLBCF2DAC6FFB574DE');
  playlistsListByChannelId('snippet,contentDetails', 'UC_x5XG1OV2P6uZZ5FSM9Ttw', 25);
  playlistsListMine('snippet,contentDetails', true);
  searchListByKeyword('snippet', 25, 'surfing', 'video');
  searchListByLocation('snippet', '21.5922529,-158.1147114', '10mi', 'surfing', 'video');
  searchListLiveEvents('snippet', 'live', 25, 'news', 'video');
  searchListMine('snippet', 25, true, 'fun', 'video');
  searchListRelatedVideos('snippet', 'Ks-_Mh1QhMc', 'video');
  subscriptionsListByChannelId('snippet,contentDetails', 'UC_x5XG1OV2P6uZZ5FSM9Ttw');
  subscriptionsListForChannelId('snippet,contentDetails', 'UC_x5XG1OV2P6uZZ5FSM9Ttw', true);
  subscriptionsListMySubscribers('snippet,contentDetails,subscriberSnippet', true);
  subscriptionsListMySubscriptions('snippet,contentDetails', true);
  videoAbuseReportReasonsList('snippet');
  videoCategoriesList('snippet', 'US');
  videoCategoriesListForRegion('snippet', 'es', 'ES');
  videosListById('snippet,contentDetails,statistics', 'Ks-_Mh1QhMc');
  videosListMostPopular('snippet,contentDetails,statistics', 'mostPopular', 'US', '');
  videosListMultipleIds('snippet,contentDetails,statistics', 'Ks-_Mh1QhMc,c0KYU2j0TM4,eIho2S0ZahI');
  videosListMyRatedVideos('snippet,contentDetails,statistics', 'like');
  videosGetRating('Ks-_Mh1QhMc,c0KYU2j0TM4,eIho2S0ZahI');
}
