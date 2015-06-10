<?php

/**
 * This sample shuffles user's existing channel sections by:
 *
 * 1. Getting the active user's channel sections via "channelSections.list" method.
 * 2. Shuffling channel sections offline.
 * 3. Saving the newly shuffled channel sections list via the "channelSections.update" method.
 *
 * @author Ibrahim Ulukaya
*/


// Call set_include_path() as needed to point to your client library.
require_once 'Google/Client.php';
require_once 'Google/Service/YouTube.php';
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

if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate($_GET['code']);
  $_SESSION['token'] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  try {

    // Call the YouTube Data API's channelSections.list method to retrieve your channel sections.
    $listResponse = $youtube->channelSections->listChannelSections('snippet,contentDetails', array('mine' => true));
    $channelSections = $listResponse['items'];

    // This will randomly reorder the items in the channel_sections list.
    shuffle($channelSections);

    $htmlBody .= "<h2>Sections Shuffled</h2><ul>";

    foreach ($channelSections as $channelSection) {
      // Each section in the list of shuffled sections is sequentially
      // set to position 0, i.e. the top.
      $channelSection['snippet']['position'] = 0;

      // Call the YouTube Data API's channelSections.update method to update a channel section.
      $updateResponse = $youtube->channelSections->update('snippet,contentDetails', $channelSection);

      $htmlBody .= sprintf('<li>%s "%s"</li>',
          $updateResponse['id'], $updateResponse['snippet']['title']);
    }

    $htmlBody .= '</ul>';

  } catch (Google_Service_Exception $e) {
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
    <title>Sections Shuffled</title>
    </head>
    <body>
      <?=$htmlBody?>
    </body>
    </html>
