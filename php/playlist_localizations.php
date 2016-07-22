<?php

/**
 * This sample sets and retrieves localized metadata for a playlist by:
 *
 * 1. Updating language of the default metadata and setting localized metadata
 *   for a playlist via "playlists.update" method.
 * 2. Getting the localized metadata for a playlist in a selected language using the
 *   "playlists.list" method and setting the "hl" parameter.
 * 3. Listing the localized metadata for a playlist using the "playlists.list" method
 *   and including "localizations" in the "part" parameter.
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
<form method="GET">
  <div>
    Action:
    <select id="action" name="action">
      <option value="set">Set Localization - Fill in: playlist ID, default language, language, , title and description</option>
      <option value="get">Get Localization- Fill in: playlist ID, language</option>
      <option value="list">List Localizations - Fill in: playlist ID, language</option>
    </select>
  </div>
  <br>
  <div>
    Playlist ID: <input type="text" id="playlistId" name="playlistId" placeholder="Enter Playlist ID">
  </div>
  <br>
  <div>
    Default Language: <input type="text" id="defaultLanguage" name="defaultLanguage" placeholder="Enter Default Language (BCP-47 language code)">
  </div>
  <br>
  <div>
    Language: <input type="text" id="language" name="language" placeholder="Enter Local Language (BCP-47 language code)">
  </div>
  <br>
  <div>
    Title: <input type="text" id="title" name="title" placeholder="Enter Title">
  </div>
  <br>
  <div>
    Description: <input type="text" id="description" name="description" placeholder="Enter Description">
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
 * authenticated user's account.
 */
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
  // This code executes if the user enters an action in the form
  // and submits the form. Otherwise, the page displays the form above.
  if (isset($_GET['action'])) {
    $resource = $_GET['resource'];
    $playlistId = $_GET['playlistId'];
    $language = $_GET['language'];
    $defaultLanguage = $_GET['defaultLanguage'];
    $title = $_GET['title'];
    $description = $_GET['description'];
    try {
      switch ($_GET['action']) {
        case 'set':
          setPlaylistLocalization($youtube, $playlistId, $defaultLanguage,
              $language, $title, $description, $htmlBody);
          break;
        case 'get':
          getPlaylistLocalization($youtube, $playlistId, $language, $htmlBody);
          break;
        case 'list':
          listPlaylistLocalizations($youtube, $playlistId, $htmlBody);
          break;
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
 * Updates a playlist's default language and sets its localized metadata.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $playlistId The id parameter specifies the playlist ID for the resource
 * that is being updated.
 * @param string $defaultLanguage The language of the playlist's default metadata
 * @param string $language The language of the localized metadata
 * @param string $title The localized title to be set
 * @param string $description The localized description to be set
 * @param $htmlBody - html body.
 */
function setPlaylistLocalization(Google_Service_YouTube $youtube, $playlistId, $defaultLanguage,
    $language, $title, $description, &$htmlBody) {
  // Call the YouTube Data API's playlists.list method to retrieve playlists.
  $playlists = $youtube->playlists->listPlaylists("snippet,localizations", array(
      'id' => $playlistId
  ));

  // If $playlists is empty, the specified playlist was not found.
  if (empty($playlists)) {
    $htmlBody .= sprintf('<h3>Can\'t find a playlist with playlist id: %s</h3>', $playlistId);
  } else {
    // Since the request specified a playlist ID, the response only
    // contains one playlist resource.
    $updatePlaylist = $playlists[0];

    // Modify playlist's default language and localizations properties.
    // Ensure that a value is set for the resource's snippet.defaultLanguage property.
    $updatePlaylist['snippet']['defaultLanguage'] = $defaultLanguage;
    $localizations = $updatePlaylist['localizations'];

    if (is_null($localizations)) {
      $localizations = array();
    }
    $localizations[$language] = array('title' => $title, 'description' => $description);
    $updatePlaylist['localizations'] = $localizations;

    // Call the YouTube Data API's playlists.update method to update an existing playlist.
    $playlistUpdateResponse = $youtube->playlists->update("snippet,localizations", $updatePlaylist);

    $htmlBody .= "<h2>Updated playlist</h2><ul>";
    $htmlBody .= sprintf('<li>(%s) default language: %s</li>', $playlistId,
        $playlistUpdateResponse['snippet']['defaultLanguage']);
    $htmlBody .= sprintf('<li>title(%s): %s</li>', $language,
        $playlistUpdateResponse['localizations'][$language]['title']);
    $htmlBody .= sprintf('<li>description(%s): %s</li>', $language,
        $playlistUpdateResponse['localizations'][$language]['description']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns localized metadata for a playlist in a selected language.
 * If the localized text is not available in the requested language,
 * this method will return text in the default language.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $playlistId The videoId parameter instructs the API to return the
 * localized metadata for the playlist specified by the playlist id.
 * @param string language The language of the localized metadata.
 * @param $htmlBody - html body.
 */
function getPlaylistLocalization(Google_Service_YouTube $youtube, $playlistId,
    $language, &$htmlBody) {
  // Call the YouTube Data API's playlists.list method to retrieve playlists.
  $playlists = $youtube->playlists->listPlaylists("snippet", array(
      'id' => $playlistId,
      'hl' => $language
  ));

  // If $playlists is empty, the specified playlist was not found.
  if (empty($playlists)) {
    $htmlBody .= sprintf('<h3>Can\'t find a playlist with playlist id: %s</h3>', $playlistId);
  } else {
    // Since the request specified a playlist ID, the response only
    // contains one playlist resource.
    $localized = $playlists[0]["snippet"]["localized"];

    $htmlBody .= "<h3>Playlist</h3><ul>";
    $htmlBody .= sprintf('<li>title(%s): %s</li>', $language, $localized['title']);
    $htmlBody .= sprintf('<li>description(%s): %s</li>', $language, $localized['description']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns a list of localized metadata for a playlist.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $playlistId The videoId parameter instructs the API to return the
 * localized metadata for the playlist specified by the playlist id.
 * @param $htmlBody - html body.
 */
function listPlaylistLocalizations(Google_Service_YouTube $youtube, $playlistId, &$htmlBody) {
  // Call the YouTube Data API's playlists.list method to retrieve playlists.
  $playlists = $youtube->playlists->listPlaylists("snippet", array(
      'id' => $playlistId
  ));

  // If $playlists is empty, the specified playlist was not found.
  if (empty($playlists)) {
    $htmlBody .= sprintf('<h3>Can\'t find a playlist with playlist id: %s</h3>', $playlistId);
  } else {
    // Since the request specified a playlist ID, the response only
    // contains one playlist resource.
    $localizations = $playlists[0]["localizations"];

    $htmlBody .= "<h3>Playlist</h3><ul>";
    foreach ($localizations as $language => $localization) {
      $htmlBody .= sprintf('<li>title(%s): %s</li>', $language, $localization['title']);
      $htmlBody .= sprintf('<li>description(%s): %s</li>', $language, $localization['description']);
    }
    $htmlBody .= '</ul>';
  }
}
?>

<!doctype html>
<html>
<head>
<title>Set and retrieve localized metadata for a playlist</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
