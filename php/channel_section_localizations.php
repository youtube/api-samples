<?php

/**
 * This sample sets and retrieves localized metadata for a channel section by:
 *
 * 1. Updating language of the default metadata and setting localized metadata
 *   for a channel section via "channelSections.update" method.
 * 2. Getting the localized metadata for a channel section in a selected language using the
 *   "channelSections.list" method and setting the "hl" parameter.
 * 3. Listing the localized metadata for a channel section using the "channelSections.list" method
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
      <option value="set">Set Localization - Fill in: channel section ID, default language, language, title</option>
      <option value="get">Get Localization- Fill in: channel section ID, language</option>
      <option value="list">List Localizations - Fill in: channel section ID, language</option>
    </select>
  </div>
  <br>
  <div>
    Channel section ID: <input type="text" id="channelSectionId" name="channelSectionId" placeholder="Enter Channel Section ID">
  </div>
  <br>
  <div>
    Default Language: <input type="text" id="defaultLanguage" name="defaultLanguage" placeholder="Enter Default Language">
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
    $channelSectionId = $_GET['channelSectionId'];
    $language = $_GET['language'];
    $defaultLanguage = $_GET['defaultLanguage'];
    $title = $_GET['title'];
    try {
      switch ($_GET['action']) {
        case 'set':
          setChannelSectionLocalization($youtube, $channelSectionId, $defaultLanguage,
              $language, $title, $htmlBody);
          break;
        case 'get':
          getChannelSectionLocalization($youtube, $channelSectionId, $language, $htmlBody);
          break;
        case 'list':
          listChannelSectionLocalizations($youtube, $channelSectionId, $htmlBody);
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
 * Updates a channel section's default language and sets its localized metadata.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $channelSectionId The id parameter specifies the channel section ID
 * for the resource that is being updated.
 * @param string $defaultLanguage The language of the channel section's default metadata
 * @param string $language The language of the localized metadata
 * @param string $title The localized title to be set
 * @param $htmlBody - html body.
 */
function setChannelSectionLocalization(Google_Service_YouTube $youtube, $channelSectionId,
    $defaultLanguage, $language, $title, &$htmlBody) {
  // Call the YouTube Data API's channelSections.list method to retrieve channel sections.
  $channelSections = $youtube->channelSections->listChannelSections("snippet,localizations", array(
      'id' => $channelSectionId
  ));

  // If $channelSections is empty, the specified channel section was not found.
  if (empty($channelSections)) {
    $htmlBody .= sprintf('<h3>Can\'t find a channel section with channel section id: %s</h3>',
        $channelSectionId);
  } else {
    // Since the request specified a channel section ID, the response only
    // contains one channel section resource.
    $updateChannelSection = $channelSections[0];

    // Modify channel section's default language and localizations properties.
    // Ensure that a value is set for the resource's snippet.defaultLanguage property.
    $updateChannelSection['snippet']['defaultLanguage'] = $defaultLanguage;
    $localizations = $updateChannelSection['localizations'];

    if (is_null($localizations)) {
      $localizations = array();
    }
    $localizations[$language] = array('title' => $title);
    $updateChannelSection['localizations'] = $localizations;

    // Call the YouTube Data API's channelSections.update method to update an
    // existing channel section.
    $channelSectionUpdateResponse = $youtube->channels->update("snippet,localizations",
        $updateChannelSection);

    $htmlBody .= "<h2>Updated channel section</h2><ul>";
    $htmlBody .= sprintf('<li>(%s) default language: %s</li>', $channelSectionId,
        $channelSectionUpdateResponse['snippet']['defaultLanguage']);
    $htmlBody .= sprintf('<li>title(%s): %s</li>', $language,
        $channelSectionUpdateResponse['localizations'][$language]['title']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns localized metadata for a channel section in a selected language.
 * If the localized text is not available in the requested language,
 * this method will return text in the default language.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $channelSectionId The channelSectionId parameter instructs the API to return the
 * localized metadata for the channel section specified by the channel section id.
 * @param string language The language of the localized metadata.
 * @param $htmlBody - html body.
 */
function getChannelSectionLocalization(Google_Service_YouTube $youtube, $channelSectionId,
    $language, &$htmlBody) {
  // Call the YouTube Data API's channelSections.list method to retrieve channel sections.
  $channelSections = $youtube->channelSections->listChannelSections("snippet", array(
      'id' => $channelSectionId,
      'hl' => $language
  ));

  // If $channelSections is empty, the specified channel section was not found.
  if (empty($channelSections)) {
    $htmlBody .= sprintf('<h3>Can\'t find a channel section with channel section id: %s</h3>',
        $channelSectionId);
  } else {
    // Since the request specified a channel section ID, the response only
    // contains one channel section resource.
    $localized = $channelSections[0]["snippet"]["localized"];

    $htmlBody .= "<h3>Channel Section</h3><ul>";
    $htmlBody .= sprintf('<li>title(%s): %s</li>', $language, $localized['title']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns a list of localized metadata for a channel section.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $channelId The channelSectionId parameter instructs the API to return the
 * localized metadata for the channel section specified by the channel section id.
 * @param $htmlBody - html body.
 */
function listChannelSectionLocalizations(Google_Service_YouTube $youtube,
    $channelSectionId, &$htmlBody) {
  // Call the YouTube Data API's channelSections.list method to retrieve channel sections.
  $channelSections = $youtube->channelSections->listChannelSections("snippet", array(
      'id' => $channelSectionId
  ));

  // If $channelSections is empty, the specified channel section was not found.
  if (empty($channelSections)) {
    $htmlBody .= sprintf('<h3>Can\'t find a channel section with channel section id: %s</h3>',
        $channelSectionId);
  } else {
    // Since the request specified a channel section ID, the response only
    // contains one channel section resource.
    $localizations = $channelSections[0]["localizations"];

    $htmlBody .= "<h3>Channel Section</h3><ul>";
    foreach ($localizations as $language => $localization) {
      $htmlBody .= sprintf('<li>title(%s): %s</li>', $language, $localization['title']);
    }
    $htmlBody .= '</ul>';
  }
}
?>

<!doctype html>
<html>
<head>
<title>Set and retrieve localized metadata for a channel section</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
