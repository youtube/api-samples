<?php

/**
 * This sample sets and retrieves localized metadata for a video by:
 *
 * 1. Updating language of the default metadata and setting localized metadata
 *   for a video via "videos.update" method.
 * 2. Getting the localized metadata for a video in a selected language using the
 *   "videos.list" method and setting the "hl" parameter.
 * 3. Listing the localized metadata for a video using the "videos.list" method and
 *   including "localizations" in the "part" parameter.
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
      <option value="set">Set Localization - Fill in: video ID, default language, language, title and description</option>
      <option value="get">Get Localization- Fill in: video ID, language</option>
      <option value="list">List Localizations - Fill in: video ID, language</option>
    </select>
  </div>
  <br>
  <div>
    Video ID: <input type="text" id="videoId" name="videoId" placeholder="Enter Video ID">
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
  // This code executes if the user enters an action in the form
  // and submits the form. Otherwise, the page displays the form above.
  if (isset($_GET['action'])) {
    $htmlBody = '';
    $videoId = $_GET['videoId'];
    $language = $_GET['language'];
    $defaultLanguage = $_GET['defaultLanguage'];
    $title = $_GET['title'];
    $description = $_GET['description'];
    try {
      switch ($_GET['action']) {
        case 'set':
          setVideoLocalization($youtube, $videoId, $defaultLanguage,
              $language, $title, $description, $htmlBody);
          break;
        case 'get':
          getVideoLocalization($youtube, $videoId, $language, $htmlBody);
          break;
        case 'list':
          listVideoLocalizations($youtube, $videoId, $htmlBody);
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
 * Updates a video's default language and sets its localized metadata.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $videoId The id parameter specifies the video ID for the resource
 * that is being updated.
 * @param string $defaultLanguage The language of the video's default metadata
 * @param string $language The language of the localized metadata
 * @param string $title The localized title to be set
 * @param string $description The localized description to be set
 * @param $htmlBody - html body.
 */
function setVideoLocalization(Google_Service_YouTube $youtube, $videoId, $defaultLanguage,
    $language, $title, $description, &$htmlBody) {
  // Call the YouTube Data API's videos.list method to retrieve videos.
  $videos = $youtube->videos->listVideos("snippet,localizations", array(
      'id' => $videoId
  ));

  // If $videos is empty, the specified video was not found.
  if (empty($videos)) {
    $htmlBody .= sprintf('<h3>Can\'t find a video with video id: %s</h3>', $videoId);
  } else {
    // Since the request specified a video ID, the response only
    // contains one video resource.
    $updateVideo = $videos[0];

    // Modify video's default language and localizations properties.
    // Ensure that a value is set for the resource's snippet.defaultLanguage property.
    $updateVideo['snippet']['defaultLanguage'] = $defaultLanguage;
    $localizations = $updateVideo['localizations'];

    if (is_null($localizations)) {
      $localizations = array();
    }
    $localizations[$language] = array('title' => $title, 'description' => $description);
    $updateVideo['localizations'] = $localizations;

    // Call the YouTube Data API's videos.update method to update an existing video.
    $videoUpdateResponse = $youtube->videos->update("snippet,localizations", $updateVideo);

    $htmlBody .= "<h2>Updated video</h2><ul>";
    $htmlBody .= sprintf('<li>(%s) default language: %s</li>', $videoId,
        $videoUpdateResponse['snippet']['defaultLanguage']);
    $htmlBody .= sprintf('<li>title(%s): %s</li>', $language,
        $videoUpdateResponse['localizations'][$language]['title']);
    $htmlBody .= sprintf('<li>description(%s): %s</li>', $language,
        $videoUpdateResponse['localizations'][$language]['description']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns localized metadata for a video in a selected language.
 * If the localized text is not available in the requested language,
 * this method will return text in the default language.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $videoId The videoId parameter instructs the API to return the
 * localized metadata for the video specified by the video id.
 * @param string language The language of the localized metadata.
 * @param $htmlBody - html body.
 */
function getVideoLocalization(Google_Service_YouTube $youtube, $videoId, $language, &$htmlBody) {
  // Call the YouTube Data API's videos.list method to retrieve videos.
  $videos = $youtube->videos->listVideos("snippet", array(
      'id' => $videoId,
      'hl' => $language
  ));

  // If $videos is empty, the specified video was not found.
  if (empty($videos)) {
    $htmlBody .= sprintf('<h3>Can\'t find a video with video id: %s</h3>', $videoId);
  } else {
    // Since the request specified a video ID, the response only
    // contains one video resource.
    $localized = $videos[0]["snippet"]["localized"];

    $htmlBody .= "<h3>Video</h3><ul>";
    $htmlBody .= sprintf('<li>title(%s): %s</li>', $language, $localized['title']);
    $htmlBody .= sprintf('<li>description(%s): %s</li>', $language, $localized['description']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns a list of localized metadata for a video.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $videoId The videoId parameter instructs the API to return the
 * localized metadata for the video specified by the video id.
 * @param $htmlBody - html body.
 */
function listVideoLocalizations(Google_Service_YouTube $youtube, $videoId, &$htmlBody) {
  // Call the YouTube Data API's videos.list method to retrieve videos.
  $videos = $youtube->videos->listVideos("snippet,localizations", array(
      'id' => $videoId
  ));

  // If $videos is empty, the specified video was not found.
  if (empty($videos)) {
    $htmlBody .= sprintf('<h3>Can\'t find a video with video id: %s</h3>', $videoId);
  } else {
    // Since the request specified a video ID, the response only
    // contains one video resource.
    $localizations = $videos[0]["localizations"];

    $htmlBody .= "<h3>Video</h3><ul>";
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
<title>Set and retrieve localized metadata for a video</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
