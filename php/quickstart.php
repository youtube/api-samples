<?php

// Call set_include_path() as needed to point to your client library.
if (!file_exists($file = __DIR__ . '/vendor/autoload.php')) {
    throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}
require_once __DIR__ . '/vendor/autoload.php';
session_start();

/*
 * This variable specifies the location of a file where the access and
 * refresh tokens will be written after successful authorization.
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
define('CREDENTIALS_PATH', '~/php-yt-oauth2.json');

function getClient() {
  $client = new Google_Client();
  // Set to name/location of your client_secret.json file.
  $client->setAuthConfigFile('client_secret.json');
  // Set to valid redirect URI for your project.
  $client->setRedirectUri('http://localhost');

  $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = file_get_contents($credentialsPath);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->authenticate($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, $accessToken);
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->refreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, $client->getAccessToken());
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Define an object that will be used to make all API requests.
$client = getClient();
$service = new Google_Service_YouTube($client);

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

if (!$client->getAccessToken()) {
  print("no access token, whaawhaaa");
  exit;
}

// Call channels.list to retrieve information 

function channelsListByUsername($service, $part, $params) {
    $params = array_filter($params);
    $response = $service->channels->listChannels(
        $part,
        $params
    );

    $description = sprintf(
        'This channel\'s ID is %s. Its title is %s, and it has %s views.',
        $response['items'][0]['id'],
        $response['items'][0]['snippet']['title'],
        $response['items'][0]['statistics']['viewCount']);
    print $description . "\n";
}

channelsListByUsername($service, 'snippet,contentDetails,statistics', array('forUsername' => 'GoogleDevelopers'));
?>
