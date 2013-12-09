<?php

/**
 * This sample adds new tags to a YouTube video by:
 *
 * 1. Retrieving the video resource by calling the "youtube.videos.list" method
 *    and setting the "id" parameter
 * 2. Appending new tags to the video resource's snippet.tags[] list
 * 3. Updating the video resource by calling the youtube.videos.update method.
 *
 * @author Ibrahim Ulukaya
*/

// Call set_include_path() as needed to point to your client library.
require_once 'Google_Client.php';
require_once 'contrib/Google_YouTubeService.php';
session_start();

/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
$OAUTH2_CLIENT_ID = 'REPLACE ME';
$OAUTH2_CLIENT_SECRET = 'REPLACE ME';

$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// Define an object that will be used to make all API requests.
$youtube = new Google_YoutubeService($client);

if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate();
  $_SESSION['token'] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  try{

    // REPLACE this value with the video ID of the video being updated.
    $videoId = "VIDEO_ID";

    // Call the API's videos.list method to retrieve the video resource.
    $listResponse = $youtube->videos->listVideos("snippet",
        array('id' => $videoId));

    $videoList = $listResponse['items'];

    // If the videoList variable is empty, the specified video was not found.
    if (empty($videoList)) {
      $htmlBody .= sprintf('<h3>Can\'t find a video with video id: %s</h3>', $videoId);
    } else {
      // Since the request specified a video ID, the response only
      // contains one video resource.
      $video = $videoList[0];
      $videoSnippet = $video['snippet'];

      $tags = $videoSnippet['tags'];

      // Preserve any tags already associated with the video. If the video does
      // not have any tags, create a new list. Replace the values "tag1" and
      // "tag2" with the new tags you want to associate with the video.
      if (is_null($tags)) {
        $tags = array("tag1", "tag2");
      } else {
        array_push($tags, "tag1", "tag2");
      }

      // Construct the video resource, using the updated tags, to send in the
      // videos.update API request.
      $updateVideo = new Google_Video($video);
      $updateSnippet = new Google_VideoSnippet($videoSnippet);
      $updateSnippet->setTags($tags);
      $updateVideo -> setSnippet($updateSnippet);

      // Update the video resource by calling the videos.update() method.
      $updateResponse = $youtube->videos->update("snippet", $updateVideo);

      $responseTags = $updateResponse['snippet']['tags'];


    $htmlBody .= "<h3>Video Updated</h3><ul>";
    $htmlBody .= sprintf('<li>Tags "%s" and "%s" added for video %s (%s) </li>',
        array_pop($responseTags), array_pop($responseTags),
        $videoId, $updateResponse['snippet']['title']);

    $htmlBody .= '</ul>';
  }
    } catch (Google_ServiceException $e) {
      $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
          htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
      $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
          htmlspecialchars($e->getMessage()));
    }

    $_SESSION['token'] = $client->getAccessToken();
    } else {
      // If the user hasn't authorized the app, initiate the OAuth flow
      $state = mt_rand();
      $client->setState($state);
      $_SESSION['state'] = $state;

      $authUrl = $client->createAuthUrl();
      $htmlBody = <<<END
  <h3>Authorization Required</h3>
  <p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
    }
    ?>

    <!doctype html>
    <html>
    <head>
    <title>Video Updated</title>
    </head>
    <body>
      <?=$htmlBody?>
    </body>
    </html>
