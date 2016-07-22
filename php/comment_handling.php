<?php

/**
 * This sample creates and manages comments by:
 *
 * 1. Getting the top-level comments for a video via "commentThreads.list" method.
 * 2. Replying to a comment thread via "comments.insert" method.
 * 3. Getting comment replies via "comments.list" method.
 * 4. Updating an existing comment via "comments.update" method.
 * 5. Sets moderation status of an existing comment via "comments.setModerationStatus" method.
 * 6. Marking a comment as spam via "comments.markAsSpam" method.
 * 7. Deleting an existing comment via "comments.delete" method.
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

/* You can replace $VIDEO_ID with one of your videos' id, and text with the
 *  comment you want to be added.
 */
$VIDEO_ID = 'REPLACE_ME';
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

    // Call the YouTube Data API's commentThreads.list method to retrieve video comment threads.
    $videoCommentThreads = $youtube->commentThreads->listCommentThreads('snippet', array(
    'videoId' => $VIDEO_ID,
    'textFormat' => 'plainText',
    ));

    $parentId = $videoCommentThreads[0]['id'];

    # Create a comment snippet with text.
    $commentSnippet = new Google_Service_YouTube_CommentSnippet();
    $commentSnippet->setTextOriginal($TEXT);
    $commentSnippet->setParentId($parentId);

    # Create a comment with snippet.
    $comment = new Google_Service_YouTube_Comment();
    $comment->setSnippet($commentSnippet);

    # Call the YouTube Data API's comments.insert method to reply to a comment.
    # (If the intention is to create a new top-level comment, commentThreads.insert
    # method should be used instead.)
    $commentInsertResponse = $youtube->comments->insert('snippet', $comment);


    // Call the YouTube Data API's comments.list method to retrieve existing comment replies.
    $videoComments = $youtube->comments->listComments('snippet', array(
        'parentId' => $parentId,
        'textFormat' => 'plainText',
    ));

    if (empty($videoComments)) {
      $htmlBody .= "<h3>Can\'t get video comments.</h3>";
    } else {
      $videoComments[0]['snippet']['textOriginal'] = 'updated';

      // Call the YouTube Data API's comments.update method to update an existing comment.
      $videoCommentUpdateResponse = $youtube->comments->update('snippet', $videoComments[0]);

      // Call the YouTube Data API's comments.setModerationStatus method to set moderation
      // status of an existing comment.
      $youtube->comments->setModerationStatus($videoComments[0]['id'], 'published');

      // Call the YouTube Data API's comments.markAsSpam method to mark an existing comment as spam.
      $youtube->comments->markAsSpam($videoComments[0]['id']);

      // Call the YouTube Data API's comments.delete method to delete an existing comment.
      $youtube->comments->delete($videoComments[0]['id']);
    }

    $htmlBody .= "<h3>Video Comment Replies</h3><ul>";
    foreach ($videoComments as $comment) {
      $htmlBody .= sprintf('<li>%s: "%s"</li>', $comment['snippet']['authorDisplayName'],
          $comment['snippet']['textOriginal']);
    }
    $htmlBody .= '</ul>';

    $htmlBody .= "<h2>Replied to a comment for</h2><ul>";
    $htmlBody .= sprintf('<li>%s: "%s"</li>',
        $commentInsertResponse['snippet']['authorDisplayName'],
        $commentInsertResponse['snippet']['textDisplay']);
    $htmlBody .= '</ul>';

    $htmlBody .= "<h2>Updated comment for</h2><ul>";
    $htmlBody .= sprintf('<li>%s: "%s"</li>',
        $videoCommentUpdateResponse['snippet']['authorDisplayName'],
        $videoCommentUpdateResponse['snippet']['textDisplay']);
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
<title>Insert, list, update, moderate, mark and delete comments.</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
