<?php

/**
 * This sample creates and manages caption tracks by:
 *
 * 1. Uploading a caption track for a video via "captions.insert" method.
 * 2. Getting the caption tracks for a video via "captions.list" method.
 * 3. Updating an existing caption track via "captions.update" method.
 * 4. Download a caption track via "captions.download" method.
 * 5. Deleting an existing caption track via "captions.delete" method.
 *
 * @author Ibrahim Ulukaya
 */

/**
 * Library Requirements
 *
 * 1. Install composer (https://getcomposer.org)
 * 2. On the command line, change to this directory (api-samples/php)
 * 3. Require the google/apiclient library
 *    $ composer require google/apiclient:~2.0
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';
session_start();

$htmlBody = <<<END
<form method="POST" enctype="multipart/form-data">
  <div>
    Action:
    <select id="action" name="action">
      <option value="upload">Upload - Fill in: video ID, caption track name, language and file</option>
      <option value="list">List - Fill in: video ID</option>
      <option value="update">Update - Fill in: caption track ID, (optional - caption track file)</option>
      <option value="download">Download - Fill in: caption track ID</option>
      <option value="delete">Delete - Fill in: caption track ID</option>
      <option value="all">All - Fill in: video ID, caption track name, language and file</option>
    </select>
  </div>
  <br>
  <div>
    Video ID: <input type="text" id="videoId" name="videoId" placeholder="Enter Video ID">
  </div>
  <br>
  <div>
    Caption Track Name: <input type="text" id="captionName" name="captionName" placeholder="Enter Caption Track Name">
  </div>
  <br>
  <div>
    Caption Track Language: <input type="text" id="captionLanguage" name="captionLanguage" placeholder="Enter Caption Track Language">
  </div>
  <br>
  <div>
    File: <input type="file" id ="captionFile" name="captionFile" accept="*/*">
  </div>
  <br>
  <div>
    Caption Track Id: <input type="text" id="captionId" name="captionId" placeholder="Enter Caption Track ID">
  </div>
  <br>
  <input type="submit" value="GO!">
</form>
END;

/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
$OAUTH2_CLIENT_ID = 'REPLACE_ME';
$OAUTH2_CLIENT_SECRET = 'REPLACE_ME';

$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);

/*
 * This OAuth 2.0 access scope allows for full read/write access to the
 * authenticated user's account and requires requests to use an SSL connection.
 */
$client->setScopes('https://www.googleapis.com/auth/youtube.force-ssl');
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);

// Check if an auth token exists for the required scopes
$tokenSessionKey = 'token-' . $client->prepareScopes();
if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate($_GET['code']);
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION[$tokenSessionKey])) {
  $client->setAccessToken($_SESSION[$tokenSessionKey]);
}

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  // This code executes if the user enters an action in the form
  // and submits the form. Otherwise, the page displays the form above.
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $videoId = isset($_POST['videoId']) ? $_POST['videoId'] : null;
    $captionFile = isset($_FILES['captionFile']) ? $_FILES['captionFile']['tmp_name'] : null;
    $captionName = isset($_POST['captionName']) ? $_POST['captionName'] : null;
    $captionLanguage = isset($_POST['captionLanguage']) ? $_POST['captionLanguage'] : null;
    $captionId = isset($_POST['captionId']) ? $_POST['captionId'] : null;
    try {
      switch ($_POST['action']) {
        case 'upload':
          uploadCaption($youtube, $client, $videoId, $captionFile,
              $captionName, $captionLanguage, $htmlBody);
          break;
        case 'list':
          $captions = listCaptions($youtube, $videoId, $htmlBody);
          break;
        case 'update':
          updateCaption($youtube, $client, $captionId, $htmlBody, $captionFile);
          break;
        case 'download':
          downloadCaption($youtube, $captionId, $htmlBody);
          break;
        case 'delete':
          deleteCaption($youtube, $captionId, $htmlBody);
          break;
        default:
          # All the available methods are used in sequence just for the sake of an example.
          uploadCaption($youtube, $client, $videoId, $captionFile,
              $captionName, $captionLanguage, $htmlBody);

          $captions = listCaptions($youtube, $videoId, $htmlBody);

          if (empty($captions)) {
            $htmlBody .= "<h3>Can't get video caption tracks.</h3>";
          } else {
            $firstCaptionId = $captions[0]['id'];
            updateCaption($youtube, $client, $firstCaptionId, $htmlBody, null);
            downloadCaption($youtube, $firstCaptionId, $htmlBody);
            deleteCaption($youtube, $firstCaptionId, $htmlBody);
        }
      }
    } catch (Google_Service_Exception $e) {
      $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
          htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
      $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
          htmlspecialchars($e->getMessage()));
    }
  }
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
} elseif ($OAUTH2_CLIENT_ID == 'REPLACE_ME') {
  $htmlBody = <<<END
  <h3>Client Credentials Required</h3>
  <p>
    You need to set <code>\$OAUTH2_CLIENT_ID</code> and
    <code>\$OAUTH2_CLIENT_ID</code> before proceeding.
  <p>
END;
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

/**
 * Uploads a caption track in draft status that matches the API request parameters.
 * (captions.insert)
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param Google_Client $client Google client.
 * @param $videoId the YouTube video ID of the video for which the API should
 *  return caption tracks.
 * @param $captionLanguage language of the caption track.
 * @param $captionName name of the caption track.
 * @param $captionFile caption track binary file.
 * @param $htmlBody html body.
 */
function uploadCaption(Google_Service_YouTube $youtube, Google_Client $client, $videoId,
    $captionFile, $captionName, $captionLanguage, &$htmlBody) {
    # Insert a video caption.
    # Create a caption snippet with video id, language, name and draft status.
    $captionSnippet = new Google_Service_YouTube_CaptionSnippet();
    $captionSnippet->setVideoId($videoId);
    $captionSnippet->setLanguage($captionLanguage);
    $captionSnippet->setName($captionName);

    # Create a caption with snippet.
    $caption = new Google_Service_YouTube_Caption();
    $caption->setSnippet($captionSnippet);

    // Specify the size of each chunk of data, in bytes. Set a higher value for
    // reliable connection as fewer chunks lead to faster uploads. Set a lower
    // value for better recovery on less reliable connections.
    $chunkSizeBytes = 1 * 1024 * 1024;

    // Setting the defer flag to true tells the client to return a request which can be called
    // with ->execute(); instead of making the API call immediately.
    $client->setDefer(true);

    // Create a request for the API's captions.insert method to create and upload a caption.
    $insertRequest = $youtube->captions->insert("snippet", $caption);

    // Create a MediaFileUpload object for resumable uploads.
    $media = new Google_Http_MediaFileUpload(
        $client,
        $insertRequest,
        '*/*',
        null,
        true,
        $chunkSizeBytes
    );
    $media->setFileSize(filesize($captionFile));


    // Read the caption file and upload it chunk by chunk.
    $status = false;
    $handle = fopen($captionFile, "rb");
    while (!$status && !feof($handle)) {
      $chunk = fread($handle, $chunkSizeBytes);
      $status = $media->nextChunk($chunk);
    }

    fclose($handle);

    // If you want to make other calls after the file upload, set setDefer back to false
    $client->setDefer(false);

    $htmlBody .= "<h2>Inserted video caption track for</h2><ul>";
    $captionSnippet = $status['snippet'];
    $htmlBody .= sprintf('<li>%s(%s) in %s language, %s status.</li>',
        $captionSnippet['name'], $status['id'], $captionSnippet['language'],
        $captionSnippet['status']);
    $htmlBody .= '</ul>';
}

/**
 * Returns a list of caption tracks. (captions.listCaptions)
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $videoId The videoId parameter instructs the API to return the
 * caption tracks for the video specified by the video id.
 * @param $htmlBody - html body.
 */
function listCaptions(Google_Service_YouTube $youtube, $videoId, &$htmlBody) {
  // Call the YouTube Data API's captions.list method to retrieve video caption tracks.
  $captions = $youtube->captions->listCaptions("snippet", $videoId);

  $htmlBody .= "<h3>Video Caption Tracks</h3><ul>";
  foreach ($captions as $caption) {
    $htmlBody .= sprintf('<li>%s(%s) in %s language</li>', $caption['snippet']['name'],
        $caption['id'],  $caption['snippet']['language']);
  }
  $htmlBody .= '</ul>';

  return $captions;
}

/**
 * Updates a caption track's draft status to publish it.
 * Updates the track with a new binary file as well if it is present.  (captions.update)
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param Google_Client $client Google client.
 * @param string $captionId The id parameter specifies the caption ID for the resource
 * that is being updated. In a caption resource, the id property specifies the
 * caption track's ID.
 * @param $htmlBody - html body.
 * @param $captionFile caption track binary file.
 */
function updateCaption(Google_Service_YouTube $youtube, Google_Client $client,
    $captionId, &$htmlBody, $captionFile) {
    // Modify caption's isDraft property to unpublish a caption track.
    $updateCaptionSnippet = new Google_Service_YouTube_CaptionSnippet();
    $updateCaptionSnippet->setIsDraft(true);

    # Create a caption with snippet.
    $updateCaption = new Google_Service_YouTube_Caption();
    $updateCaption->setSnippet($updateCaptionSnippet);
    $updateCaption->setId($captionId);

    if ($captionFile == '')
    {
      // Call the YouTube Data API's captions.update method to update an existing caption track.
      $captionUpdateResponse = $youtube->captions->update("snippet", $updateCaption);

      $htmlBody .= "<h2>Updated caption track</h2><ul>";
      $htmlBody .= sprintf('<li>%s(%s) draft status: %s</li>',
          $captionUpdateResponse['snippet']['name'],
      $captionUpdateResponse['id'],  $captionUpdateResponse['snippet']['isDraft']);
      $htmlBody .= '</ul>';
    } else {
      // Specify the size of each chunk of data, in bytes. Set a higher value for
      // reliable connection as fewer chunks lead to faster uploads. Set a lower
      // value for better recovery on less reliable connections.
      $chunkSizeBytes = 1 * 1024 * 1024;

      // Setting the defer flag to true tells the client to return a request which can be called
      // with ->execute(); instead of making the API call immediately.
      $client->setDefer(true);

      // Create a request for the YouTube Data API's captions.update method to update
      // an existing caption track.
      $captionUpdateRequest = $youtube->captions->update("snippet", $updateCaption);

      // Create a MediaFileUpload object for resumable uploads.
      $media = new Google_Http_MediaFileUpload(
          $client,
          $captionUpdateRequest,
          '*/*',
          null,
          true,
          $chunkSizeBytes
      );
      $media->setFileSize(filesize($captionFile));

      // Read the caption file and upload it chunk by chunk.
      $status = false;
      $handle = fopen($captionFile, "rb");
      while (!$status && !feof($handle)) {
        $chunk = fread($handle, $chunkSizeBytes);
        $status = $media->nextChunk($chunk);
      }

      fclose($handle);

      // If you want to make other calls after the file upload, set setDefer back to false
      $client->setDefer(false);

      $htmlBody .= "<h2>Updated caption track</h2><ul>";
      $htmlBody .= sprintf('<li>%s(%s) draft status: %s and updated the track with
          the new uploaded file.</li>',
          $status['snippet']['name'], $status['id'],  $status['snippet']['isDraft']);
      $htmlBody .= '</ul>';
    }
}

/**
 * Downloads a caption track for a YouTube video. (captions.download)
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $captionId The id parameter specifies the caption ID for the resource
 * that is being downloaded. In a caption resource, the id property specifies the
 * caption track's ID.
 * @param $htmlBody - html body.
 */
function downloadCaption(Google_Service_YouTube $youtube, $captionId, &$htmlBody) {
    // Call the YouTube Data API's captions.download method to download an existing caption.
    $captionResouce = $youtube->captions->download($captionId, array(
        'tfmt' => "srt",
        'alt' => "media"
    ));

    $htmlBody .= "<h2>Downloaded caption track</h2><ul>";
    $htmlBody .= sprintf('<li>%s</li>',
      $captionResouce);
    $htmlBody .= '</ul>';
}

/**
 * Deletes a caption track for a YouTube video. (captions.delete)
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $captionId The id parameter specifies the caption ID for the resource
 * that is being deleted. In a caption resource, the id property specifies the
 * caption track's ID.
 * @param $htmlBody - html body.
 */
function deleteCaption(Google_Service_YouTube $youtube, $captionId, &$htmlBody) {
    // Call the YouTube Data API's captions.delete method to delete a caption.
    $youtube->captions->delete($captionId);

    $htmlBody .= "<h2>Deleted caption track</h2><ul>";
    $htmlBody .= sprintf('<li>%s</li>',$captionId);
    $htmlBody .= '</ul>';
}
?>

<!doctype html>
<html>
<head>
<title>Create and manage video caption tracks</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
