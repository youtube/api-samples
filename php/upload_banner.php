<?php

/**
 * This sample sets a custom banner for a user's channel by:
 *
 * 1. Uploading a banner image with "youtube.channelBanners.insert" method via resumable upload
 * 2. Getting user's channel object with "youtube.channels.list" method and "mine" parameter
 * 3. Updating channel's banner external URL with "youtube.channels.update" method
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
$client->setScopes('https://www.googleapis.com/auth/youtube');
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
  $htmlBody = '';
  try{

    // REPLACE with the path to your file that you want to upload for thumbnail
    $imagePath = "/path/to/file.jpg";

    // Specify the size of each chunk of data, in bytes. Set a higher value for
    // reliable connection as fewer chunks lead to faster uploads. Set a lower
    // value for better recovery on less reliable connections.
    $chunkSizeBytes = 1 * 1024 * 1024;

    // Setting the defer flag to true tells the client to return a request which can be called
    // with ->execute(); instead of making the API call immediately.
    $client->setDefer(true);

    $chan = new Google_Service_YouTube_ChannelBannerResource();

    // Create a request for the API's channelBanners.insert method to upload the banner.
    $insertRequest = $youtube->channelBanners->insert($chan);

    // Create a MediaFileUpload object for resumable uploads.
    $media = new Google_Http_MediaFileUpload(
        $client,
        $insertRequest,
        'image/jpeg',
        null,
        true,
        $chunkSizeBytes
    );
    $media->setFileSize(filesize($imagePath));


    // Read the media file and upload it chunk by chunk.
    $status = false;
    $handle = fopen($imagePath, "rb");
    while (!$status && !feof($handle)) {
      $chunk = fread($handle, $chunkSizeBytes);
      $status = $media->nextChunk($chunk);
    }

    fclose($handle);

    // If you want to make other calls after the file upload, set setDefer back to false
    $client->setDefer(false);

    $thumbnailUrl = $status['url'];

    // Call the API's channels.list method with mine parameter to fetch authorized user's channel.
    $listResponse = $youtube->channels->listChannels('brandingSettings', array(
        'mine' => 'true',
    ));

    $responseChannel = $listResponse[0];
    $responseChannel['brandingSettings']['image']['bannerExternalUrl']=$thumbnailUrl;

     // Call the API's channels.update method to update branding settings of the channel.
     $updateResponse = $youtube->channels->update('brandingSettings', $responseChannel);

     $bannerMobileUrl = $updateResponse["brandingSettings"]["image"]["bannerMobileImageUrl"];

     $htmlBody .= "<h3>Thumbnail Uploaded</h3><ul>";
     $htmlBody .= sprintf('<li>%s</li>',
         $thumbnailUrl);
     $htmlBody .= sprintf('<img src="%s">', $bannerMobileUrl);
     $htmlBody .= '</ul>';

  } catch (Google_Service_Exception $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
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
?>

<!doctype html>
<html>
<head>
<title>Banner Uploaded and Set</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
