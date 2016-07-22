<?php

/**
 * This sample creates a channel section by :
 *
 * 1. Getting the active user's channel branding settings via "channels.list" method.
 * 2. Updating the active user's channel to show "browse view" via "channel.update" method.
 * 3. Creating a channel section in the active user's channel via "channelSections->insert" method.
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
if (!file_exists($file = __DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';
session_start();

// Valid section types.
$SECTION_TYPES = array("allPlaylists", "completedEvents", "likedPlaylists",
  "likes", "liveEvents", "multipleChannels", "multiplePlaylists",
  "popularUploads", "recentActivity", "recentPosts", "recentUploads",
  "singlePlaylist", "upcomingEvents");

// Valid section styles.
$SECTION_STYLES = array("horizontalRow", "verticalList");

// Replace with section title of your choice.
$SECTION_TITLE = 'YOUR_SECTION_TITLE';

// The section's position on the channel page. This property uses a 0-based index.
// If you do not specify a value for this property when inserting a channel section,
// the default behavior is to display the new section last.
$SECTION_POSITION = 0;

// A list of playlist IDs that will be featured in a channel section.
$PLAYLISTS = '';

// A list of channel IDs that will be  featured in a channel section.
$CHANNELS = '';

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
  try {

    /*
     * Before channel shelves will appear on your channel's web page, browse
     * view needs to be enabled. If you know that your channel already has
     * it enabled, or if you want to add a number of sections before enabling it,
     * you can skip the call to enable_browse_view().
     */

    // Call the YouTube Data API's channels.list method to retrieve your channel.
    $listResponse = $youtube->channels->listChannels('brandingSettings', array('mine' => true));
    $channel = $listResponse['items'][0];
    $channel['brandingSettings']['channel']['showBrowseView'] = true;

    // Call the YouTube Data API's channels.update method to update your channel.
    $updateResponse = $youtube->channels->update('brandingSettings', $channel);

    // Create a channel section snippet object with type, style, title and position.
    $channelSectionSnippet = new Google_Service_YouTube_ChannelSectionSnippet();
    $channelSectionSnippet->setType($SECTION_TYPES[0]);
    $channelSectionSnippet->setStyle($SECTION_STYLES[0]);
    $channelSectionSnippet->setTitle($SECTION_TITLE);
    $channelSectionSnippet->setPosition($SECTION_POSITION);

    // Create a channel section contentDetails object with channels and playlists.
    $channelSectionContentDetails = new Google_Service_YouTube_ChannelSectionContentDetails();
    $channelSectionContentDetails->setChannels($CHANNELS);
    $channelSectionContentDetails->setPlaylists($PLAYLISTS);

    // Create a channel section with snippet and contentDetails.
    $channelSection = new Google_Service_YouTube_ChannelSection();
    $channelSection->setSnippet($channelSectionSnippet);
    $channelSection->setContentDetails($channelSectionContentDetails);

    // Call the YouTube Data API's channelSections.insert method to create a channel section.
    $insertResponse = $youtube->channelSections->insert('snippet,contentDetails', $channelSection);

    $htmlBody = "<h2>Section Created</h2><ul>";
    $htmlBody .= sprintf('<li>%s "%s"</li>',
        $insertResponse['id'], $insertResponse['snippet']['title']);
    $htmlBody .= '</ul>';

    $htmlBody .= "<h3>with channels</h3><ul>";
    foreach ($insertResponse['contentDetails']['channels'] as $channel) {
      $channels .= sprintf('<li>%s</li>', $channel);
    }
    $htmlBody .= '</ul>';

    $htmlBody .= "<h3>with playlists</h3><ul>";
    foreach ($insertResponse['contentDetails']['playlists'] as $playlist) {
      $channels .= sprintf('<li>%s</li>', $playlist);
    }
    $htmlBody .= '</ul>';

  } catch (Google_Service_Exception $e) {
    $htmlBody = sprintf('<p>A service error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody = sprintf('<p>An client error occurred: <code>%s</code></p>',
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
    <title>Section Created</title>
    </head>
    <body>
      <?=$htmlBody?>
    </body>
    </html>
