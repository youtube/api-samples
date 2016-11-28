/**
 * Title: Search by keyword
 * Method: youtube.search.list
 * This function searches for videos related to the keyword 'dogs'. The video IDs and titles
 * of the search results are logged to Apps Script's log.
 *
 * Note that this sample limits the results to 25. To return more results, pass
 * additional parameters as documented here:
 *   https://developers.google.com/youtube/v3/docs/search/list
 */
function searchByKeyword() {
  var results = YouTube.Search.list('id,snippet', {q: 'dogs', maxResults: 25});

  for(var i in results.items) {
    var item = results.items[i];
    Logger.log('[%s] Title: %s', item.id.videoId, item.snippet.title);
  }
}


/**
 * Title: Retrieve my uploads
 * Method: youtube.channels.list
 * This function retrieves the current script user's uploaded videos. To execute,
 * it requires the OAuth read/write scope for YouTube as well as user authorization.
 * In Apps Script's runtime environment, the first time a user runs a script, Apps
 * Script will prompt the user for permission to access the services called by the
 * script. After permissions are granted, they are cached for some periodF of time.
 * The user running the script will be prompted for permission again once the
 * permissions required change, or when they are invalidated by the
 * ScriptApp.invalidateAuth() function.
 *
 * This script takes the following steps to retrieve the active user's uploaded videos:
 *    1. Fetches the user's channels
 *    2. Fetches the user's 'uploads' playlist
 *    3. Iterates through this playlist and logs the video IDs and titles
 *    4. Fetches a next page token (if any). If there is one, fetches the next page. GOTO Step 3
 */
function retrieveMyUploads() {
  var results = YouTube.Channels.list('contentDetails', {mine: true});

  for(var i in results.items) {
    var item = results.items[i];
    // Get the playlist ID, which is nested in contentDetails, as described in the
    // Channel resource: https://developers.google.com/youtube/v3/docs/channels
    var playlistId = item.contentDetails.relatedPlaylists.uploads;

    var nextPageToken = '';

    // This loop retrieves a set of playlist items and checks the nextPageToken in the
    // response to determine whether the list contains additional items. It repeats that process
    // until it has retrieved all of the items in the list.
    while (nextPageToken != null) {
      var playlistResponse = YouTube.PlaylistItems.list('snippet', {
        playlistId: playlistId,
        maxResults: 25,
        pageToken: nextPageToken
      });

      for (var j = 0; j < playlistResponse.items.length; j++) {
        var playlistItem = playlistResponse.items[j];
        Logger.log('[%s] Title: %s',
                   playlistItem.snippet.resourceId.videoId,
                   playlistItem.snippet.title);

      }
      nextPageToken = playlistResponse.nextPageToken;
    }
  }
}

/**
 * Title: Update video
 * Method: youtube.videos.update
 * This sample finds the active user's uploads, then updates the most recent
 * upload's description by appending a string.
 */
function updateVideo() {
  // 1. Fetch all the channels owned by active user
  var myChannels = YouTube.Channels.list('contentDetails', {mine: true});

  // 2. Iterate through the channels and get the uploads playlist ID
  for (var i = 0; i < myChannels.items.length; i++) {
    var item = myChannels.items[i];
    var uploadsPlaylistId = item.contentDetails.relatedPlaylists.uploads;

    var playlistResponse = YouTube.PlaylistItems.list('snippet', {
      playlistId: uploadsPlaylistId,
      maxResults: 1
    });

    // Get the videoID of the first video in the list
    var video = playlistResponse.items[0];
    var originalDescription = video.snippet.description;
    var updatedDescription = originalDescription + ' Description updated via Google Apps Script';

    video.snippet.description = updatedDescription;

    var resource = {
      snippet: {
        title: video.snippet.title,
        description: updatedDescription,
        categoryId: '22'
      },
      id: video.snippet.resourceId.videoId
    };
    YouTube.Videos.update(resource, 'id,snippet');
  }
}

/**
 * Title: Subscribe to channel
 * Method: youtube.subscriptions.insert
 * This sample subscribes the active user to the GoogleDevelopers
 * YouTube channel, specified by the channelId.
 */
function addSubscription() {
  // Replace this channel ID with the channel ID you want to subscribe to
  var channelId = 'UC9gFih9rw0zNCK3ZtoKQQyA';

  var resource = {
    snippet: {
      resourceId: {
        kind: 'youtube#channel',
        channelId: channelId
      }
    }
  };

  try {
    var response = YouTube.Subscriptions.insert(resource, 'snippet');
    Logger.log(response);
  } catch (e) {
    if(e.message.match('subscriptionDuplicate')) {
      Logger.log('Cannot subscribe; already subscribed to channel: ' + channelId);
    } else {
      Logger.log('Error adding subscription: ' + e.message);
    }
  }
}

/**
 * Title: Post channel bulletin
 * Method: youtube.activities.insert
 * This function creates and posts a new channel bulletin, adding a video and message. Note that this
 * will also accept a playlist ID. After completing the API call, logs the output to the log.
 */
function postChannelBulletin() {
  var message = 'Thanks for subscribing to my channel!  This posting is from Google Apps Script';
  var videoId = 'qZRsVqOIWms';

  var resource = {
    snippet: {
      description: message
    },
    contentDetails: {
      bulletin: {
        resourceId: {
          kind: 'youtube#video',
          videoId: videoId
        }
      }
    }
  };

  var response = YouTube.Activities.insert(resource, 'snippet,contentDetails');
  Logger.log(response);
}

/**
 * Title: Export YouTube Analytics data to Google Sheets
 * Method: youtubeAnalytics.reports.query
 * This function uses the YouTube Analytics API to fetch data about the
 * authenticated user's channel, creating a new Google Sheet in the user's Drive
 * with the data.
 *
 * The first part of this sample demonstrates a simple YouTube Analytics API
 * call. This function first fetches the active user's channel ID. Using that
 * ID, the function makes a YouTube Analytics API call to retrieve views,
 * likes, dislikes and shares for the last 30 days. The API returns the data
 * in a response object that contains a 2D array.
 *
 * The second part of the sample constructs a Spreadsheet. This spreadsheet
 * is placed in the authenticated user's Google Drive with the name
 * 'YouTube Report' and date range in the title. The function populates the
 * spreadsheet with the API response, then locks columns and rows that will
 * define a chart axes. A stacked column chart is added for the spreadsheet.
 */
function spreadsheetAnalytics() {
  // Get the channel ID
  var myChannels = YouTube.Channels.list('id', {mine: true});
  var channel = myChannels.items[0];
  var channelId = channel.id;

  // Set the dates for our report
  var today = new Date();
  var oneMonthAgo = new Date();
  oneMonthAgo.setMonth(today.getMonth() - 1);
  var todayFormatted = Utilities.formatDate(today, 'UTC', 'yyyy-MM-dd')
  var oneMonthAgoFormatted = Utilities.formatDate(oneMonthAgo, 'UTC', 'yyyy-MM-dd');

  // The YouTubeAnalytics.Reports.query() function has four required parameters and one optional
  // parameter. The first parameter identifies the channel or content owner for which you are
  // retrieving data. The second and third parameters specify the start and end dates for the
  // report, respectively. The fourth parameter identifies the metrics that you are retrieving.
  // The fifth parameter is an object that contains any additional optional parameters
  // (dimensions, filters, sort, etc.) that you want to set.
  var analyticsResponse = YouTubeAnalytics.Reports.query(
    'channel==' + channelId,
    oneMonthAgoFormatted,
    todayFormatted,
    'views,likes,dislikes,shares',
    {
      dimensions: 'day',
      sort: '-day'
    });

  // Create a new Spreadsheet with rows and columns corresponding to our dates
  var ssName = 'YouTube channel report ' + oneMonthAgoFormatted + ' - ' + todayFormatted;
  var numRows = analyticsResponse.rows.length;
  var numCols = analyticsResponse.columnHeaders.length;

  // Add an extra row for column headers
  var ssNew = SpreadsheetApp.create(ssName, numRows + 1, numCols);

  // Get the first sheet
  var sheet = ssNew.getSheets()[0];

  // Get the range for the title columns
  // Remember, spreadsheets are 1-indexed, whereas arrays are 0-indexed
  var headersRange = sheet.getRange(1, 1, 1, numCols);
  var headers = [];

  // These column headers will correspond with the metrics requested
  // in the initial call: views, likes, dislikes, shares
  for(var i in analyticsResponse.columnHeaders) {
    var columnHeader = analyticsResponse.columnHeaders[i];
    var columnName = columnHeader.name;
    headers[i] = columnName;
  }
  // This takes a 2 dimensional array
  headersRange.setValues([headers]);

  // Bold and freeze the column names
  headersRange.setFontWeight('bold');
  sheet.setFrozenRows(1);

  // Get the data range and set the values
  var dataRange = sheet.getRange(2, 1, numRows, numCols);
  dataRange.setValues(analyticsResponse.rows);

  // Bold and freeze the dates
  var dateHeaders = sheet.getRange(1, 1, numRows, 1);
  dateHeaders.setFontWeight('bold');
  sheet.setFrozenColumns(1);

  // Include the headers in our range. The headers are used
  // to label the axes
  var range = sheet.getRange(1, 1, numRows, numCols);
  var chart = sheet.newChart()
                   .asColumnChart()
                   .setStacked()
                   .addRange(range)
                   .setPosition(4, 2, 10, 10)
                   .build();
  sheet.insertChart(chart);

}
