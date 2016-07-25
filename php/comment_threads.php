<?php

/**
 * This sample creates and manages top-level comments by:
 *
 * 1. Creating a top-level comments for a video and a channel via "commentThreads.insert" method.
 * 2. Getting the top-level comments for a video and a channel via "commentThreads.list" method.
 * 3. Updating an existing comments via "commentThreads.update" method.
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

/* You can replace $VIDEO_ID with one of your videos' id, channel id with your channel's id,
 * and text with the comment you want to be added.
 */
$VIDEO_ID = 'REPLACE_ME';
$CHANNEL_ID = 'REPLACE_ME';
$TEXT = 'REPLACE_ME';

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
  try {
    # All the available methods are used in sequence just for the sake of an example.

    # Insert channel comment by omitting videoId.
    # Create a comment snippet with text.
    $commentSnippet = new Google_Service_YouTube_CommentSnippet();
    $commentSnippet->setTextOriginal($TEXT);

    # Create a top-level comment with snippet.
    $topLevelComment = new Google_Service_YouTube_Comment();
    $topLevelComment->setSnippet($commentSnippet);

    # Create a comment thread snippet with channelId and top-level comment.
    $commentThreadSnippet = new Google_Service_YouTube_CommentThreadSnippet();
    $commentThreadSnippet->setChannelId($CHANNEL_ID);
    $commentThreadSnippet->setTopLevelComment($topLevelComment);

    # Create a comment thread with snippet.
    $commentThread = new Google_Service_YouTube_CommentThread();
    $commentThread->setSnippet($commentThreadSnippet);

    // Call the YouTube Data API's commentThreads.insert method to create a comment.
    $channelCommentInsertResponse = $youtube->commentThreads->insert('snippet', $commentThread);


    # Insert video comment
    $commentThreadSnippet->setVideoId($VIDEO_ID);
    // Call the YouTube Data API's commentThreads.insert method to create a comment.
    $videoCommentInsertResponse = $youtube->commentThreads->insert('snippet', $commentThread);

    // Call the YouTube Data API's commentThreads.list method to retrieve video comment threads.
    $videoComments = $youtube->commentThreads->listCommentThreads('snippet', array(
        'videoId' => $VIDEO_ID,
        'textFormat' => 'plainText',
    ));

    if (empty($videoComments)) {
      $htmlBody .= "<h3>Can\'t get video comments.</h3>";
    } else {
      $videoComments[0]['snippet']['topLevelComment']['snippet']['textOriginal'] = 'updated';
      $videoCommentUpdateResponse = $youtube->commentThreads->update('snippet', $videoComments[0]);
    }

    // Call the YouTube Data API's commentThreads.list method to retrieve channel comment threads.
    $channelComments = $youtube->commentThreads->listCommentThreads('snippet', array(
        'channelId' => $CHANNEL_ID,
        'textFormat' => 'plainText',
    ));

    if (empty($channelComments)) {
      $htmlBody .= "<h3>Can\'t get channel comments.</h3>";
    } else {
      $channelComments[0]['snippet']['topLevelComment']['snippet']['textOriginal'] = 'updated';
      $channelCommentUpdateResponse = $youtube->commentThreads->update('snippet', $channelComments[0]);
    }

    $htmlBody .= "<h2>Inserted channel comment for</h2><ul>";
    $comment = $channelCommentInsertResponse['snippet']['topLevelComment'];
    $htmlBody .= sprintf('<li>%s: "%s"</li>',
        $comment['snippet']['authorDisplayName'], $comment['snippet']['textDisplay']);
    $htmlBody .= '</ul>';

    $htmlBody .= "<h2>Inserted video comment for</h2><ul>";
    $comment = $videoCommentInsertResponse['snippet']['topLevelComment'];
    $htmlBody .= sprintf('<li>%s: "%s"</li>',
        $comment['snippet']['authorDisplayName'], $comment['snippet']['textDisplay']);
    $htmlBody .= '</ul>';

    $htmlBody .= "<h3>Video Comments</h3><ul>";
    foreach ($videoComments as $comment) {
      $htmlBody .= sprintf('<li>%s</li>', $comment['snippet']['topLevelComment']['snippet']['textOriginal']);
    }
    $htmlBody .= '</ul>';

    $htmlBody .= "<h3>Channel Comments</h3><ul>";
    foreach ($channelComments as $comment) {
      $htmlBody .= sprintf('<li>%s</li>', $comment['snippet']['topLevelComment']['snippet']['textOriginal']);
    }
    $htmlBody .= '</ul>';

    $htmlBody .= "<h2>Updated channel comment for</h2><ul>";
    $comment = $videoCommentUpdateResponse['snippet']['topLevelComment'];
    $htmlBody .= sprintf('<li>%s: "%s"</li>',
        $comment['snippet']['authorDisplayName'], $comment['snippet']['textDisplay']);
    $htmlBody .= '</ul>';

    $htmlBody .= "<h2>Updated video comment for</h2><ul>";
    $comment = $channelCommentUpdateResponse['snippet']['topLevelComment'];
    $htmlBody .= sprintf('<li>%s: "%s"</li>',
        $comment['snippet']['authorDisplayName'], $comment['snippet']['textDisplay']);
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
<title>Insert, list and update top-level comments</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
