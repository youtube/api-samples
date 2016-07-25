<?php

/**
 * This sample sets and retrieves localized metadata for a channel by:
 *
 * 1. Updating language of the default metadata and setting localized metadata
 *   for a channel via "channels.update" method.
 * 2. Getting the localized metadata for a channel in a selected language using the
 *   "channels.list" method and setting the "hl" parameter.
 * 3. Listing the localized metadata for a channel using "channels.list" method and
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
      <option value="set">Set Localization - Fill in: channel ID, default language, language, description</option>
      <option value="get">Get Localization- Fill in: channel ID, language</option>
      <option value="list">List Localizations - Fill in: channel ID, language</option>
    </select>
  </div>
  <br>
  <div>
    Channel ID: <input type="text" id="channelId" name="channelId" placeholder="Enter Channel ID">
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
    $resource = $_GET['resource'];
    $channelId = $_GET['channelId'];
    $language = $_GET['language'];
    $defaultLanguage = $_GET['defaultLanguage'];
    $description = $_GET['description'];
    try {
      switch ($_GET['action']) {
        case 'set':
          setChannelLocalization($youtube, $channelId, $defaultLanguage,
              $language, $description, $htmlBody);
          break;
        case 'get':
          getChannelLocalization($youtube, $channelId, $language, $htmlBody);
          break;
        case 'list':
          listChannelLocalizations($youtube, $channelId, $htmlBody);
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
 * Updates a channel's default language and sets its localized metadata.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $channelId The id parameter specifies the channel ID for the resource
 * that is being updated.
 * @param string $defaultLanguage The language of the channel's default metadata
 * @param string $language The language of the localized metadata
 * @param string $description The localized description to be set
 * @param $htmlBody - html body.
 */
function setChannelLocalization(Google_Service_YouTube $youtube, $channelId, $defaultLanguage,
    $language, $description, &$htmlBody) {
  // Call the YouTube Data API's channels.list method to retrieve channels.
  $channels = $youtube->channels->listChannels("brandingSettings,localizations", array(
      'id' => $channelId
  ));

  // If $channels is empty, the specified channel was not found.
  if (empty($channels)) {
    $htmlBody .= sprintf('<h3>Can\'t find a channel with channel id: %s</h3>', $channelId);
  } else {
    // Since the request specified a channel ID, the response only
    // contains one channel resource.
    $updateChannel = $channels[0];

    // Modify channel's default language and localizations properties.
    // Ensure that a value is set for the resource's snippet.defaultLanguage property.
    // To set the snippet.defaultLanguage property for a channel resource,
    // you actually need to update the brandingSettings.channel.defaultLanguage property.
    $updateChannel['brandingSettings']['channel']['defaultLanguage'] = $defaultLanguage;
    $localizations = $updateChannel['localizations'];

    if (is_null($localizations)) {
      $localizations = array();
    }
    $localizations[$language] = array('description' => $description);
    $updateChannel['localizations'] = $localizations;

    // Call the YouTube Data API's channels.update method to update an existing channel.
    $channelUpdateResponse = $youtube->channels->update("brandingSettings,localizations",
        $updateChannel);

    $htmlBody .= "<h2>Updated channel</h2><ul>";
    $htmlBody .= sprintf('<li>(%s) default language: %s</li>', $channelId,
        $channelUpdateResponse['brandingSettings']['channel']['defaultLanguage']);
    $htmlBody .= sprintf('<li>description(%s): %s</li>', $language,
        $channelUpdateResponse['localizations'][$language]['description']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns localized metadata for a channel in a selected language.
 * If the localized text is not available in the requested language,
 * this method will return text in the default language.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $channelId The channelId parameter instructs the API to return the
 * localized metadata for the channel specified by the channel id.
 * @param string language The language of the localized metadata.
 * @param $htmlBody - html body.
 */
function getChannelLocalization(Google_Service_YouTube $youtube, $channelId,
    $language, &$htmlBody) {
  // Call the YouTube Data API's channels.list method to retrieve channels.
  $channels = $youtube->channels->listChannels("snippet", array(
      'id' => $channelId,
      'hl' => $language
  ));

  // If $channels is empty, the specified channel was not found.
  if (empty($channels)) {
    $htmlBody .= sprintf('<h3>Can\'t find a channel with channel id: %s</h3>', $channelId);
  } else {
    // Since the request specified a channel ID, the response only
    // contains one channel resource.
    $localized = $channels[0]["snippet"]["localized"];

    $htmlBody .= "<h3>Channel</h3><ul>";
    $htmlBody .= sprintf('<li>description(%s): %s</li>', $language, $localized['description']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns a list of localized metadata for a channel.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $channelId The channelId parameter instructs the API to return the
 * localized metadata for the channel specified by the channel id.
 * @param $htmlBody - html body.
 */
function listChannelLocalizations(Google_Service_YouTube $youtube, $channelId, &$htmlBody) {
  // Call the YouTube Data API's channels.list method to retrieve channels.
  $channels = $youtube->channels->listChannels("snippet,localizations", array(
      'id' => $channelId
  ));

  // If $channels is empty, the specified channel was not found.
  if (empty($channels)) {
    $htmlBody .= sprintf('<h3>Can\'t find a channel with channel id: %s</h3>', $channelId);
  } else {
    // Since the request specified a channel ID, the response only
    // contains one channel resource.
    $localizations = $channels[0]["localizations"];

    $htmlBody .= "<h3>Channel</h3><ul>";
    foreach ($localizations as $language => $localization) {
      $htmlBody .= sprintf('<li>description(%s): %s</li>', $language, $localization['description']);
    }
    $htmlBody .= '</ul>';
  }
}
?>

<!doctype html>
<html>
<head>
<title>Set and retrieve localized metadata for a channel</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
