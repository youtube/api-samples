<?php

/**
 * This sample lists videos that are associated with a particular keyword and are in the radius of
 *   particular geographic coordinates by:
 *
 * 1. Searching videos with "youtube.search.list" method and setting "type", "q", "location" and
 *   "locationRadius" parameters.
 * 2. Retrieving location details for each video with "youtube.videos.list" method and setting
 *   "id" parameter to comma separated list of video IDs in search result.
 *
 * @author Ibrahim Ulukaya
 */

$htmlBody = <<<END
<form method="GET">
  <div>
    Search Term: <input type="search" id="q" name="q" placeholder="Enter Search Term">
  </div>
  <div>
    Location: <input type="text" id="location" name="location" placeholder="37.42307,-122.08427">
  </div>
  <div>
    Location Radius: <input type="text" id="locationRadius" name="locationRadius" placeholder="5km">
  </div>
  <div>
    Max Results: <input type="number" id="maxResults" name="maxResults" min="1" max="50" step="1" value="25">
  </div>
  <input type="submit" value="Search">
</form>
END;

// This code executes if the user enters a search query in the form
// and submits the form. Otherwise, the page displays the form above.
if ($_GET['q'] && $_GET['maxResults']) {
  // Call set_include_path() as needed to point to your client library.
  require_once 'Google/Client.php';
  require_once 'Google/Service/YouTube.php';

  /*
   * Set $DEVELOPER_KEY to the "API key" value from the "Access" tab of the
  * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
  * Please ensure that you have enabled the YouTube Data API for your project.
  */
  $DEVELOPER_KEY = 'REPLACE_ME';

  $client = new Google_Client();
  $client->setDeveloperKey($DEVELOPER_KEY);

  // Define an object that will be used to make all API requests.
  $youtube = new Google_Service_YouTube($client);

  try {
    // Call the search.list method to retrieve results matching the specified
    // query term.
    $searchResponse = $youtube->search->listSearch('id,snippet', array(
        'type' => 'video',
        'q' => $_GET['q'],
        'location' =>  $_GET['location'],
        'locationRadius' =>  $_GET['locationRadius'],
        'maxResults' => $_GET['maxResults'],
    ));

    $videoResults = array();
    # Merge video ids
    foreach ($searchResponse['items'] as $searchResult) {
      array_push($videoResults, $searchResult['id']['videoId']);
    }
    $videoIds = join(',', $videoResults);

    # Call the videos.list method to retrieve location details for each video.
    $videosResponse = $youtube->videos->listVideos('snippet, recordingDetails', array(
    'id' => $videoIds,
    ));

    $videos = '';

    // Display the list of matching videos.
    foreach ($videosResponse['items'] as $videoResult) {
      $videos .= sprintf('<li>%s (%s,%s)</li>',
          $videoResult['snippet']['title'],
          $videoResult['recordingDetails']['location']['latitude'],
          $videoResult['recordingDetails']['location']['longitude']);
    }

    $htmlBody .= <<<END
    <h3>Videos</h3>
    <ul>$videos</ul>
END;
  } catch (Google_Service_Exception $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  }
}
?>

<!doctype html>
<html>
<head>
<title>YouTube Geolocation Search</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
