<?php

/**
 * This sample creates a reporting job by:
 *
 * 1. Listing the available report types using the "reportTypes.list" method.
 * 2. Creating a reporting job using the "jobs.create" method.
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

/*
 * This OAuth 2.0 access scope allows for read access to the YouTube Analytics monetary reports for
 * authenticated user's account. Any request that retrieves earnings or ad performance metrics must
 * use this scope.
 */
$client->setScopes('https://www.googleapis.com/auth/yt-analytics-monetary.readonly');
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// YouTube Reporting object used to make YouTube Reporting API requests.
$youtubeReporting = new Google_Service_YouTubeReporting($client);

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
  // This code executes if the user enters a name in the form
  // and submits the form. Otherwise, the page displays the form above.
  try {
    if (empty(listReportTypes($youtubeReporting, $htmlBody))) {
      $htmlBody .= sprintf('<p>No report types found.</p>');
    } else if ($_GET['reportTypeId']){
      createReportingJob($youtubeReporting, $_GET['reportTypeId'], $_GET['jobName'], $htmlBody);
    }
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


/**
 * Creates a reporting job. (jobs.create)
 *
 * @param Google_Service_YouTubereporting $youtubeReporting YouTube Reporting service object.
 * @param string $reportTypeId Id of the job's report type.
 * @param string $name name of the job.
 * @param $htmlBody - html body.
 */
function createReportingJob(Google_Service_YouTubeReporting $youtubeReporting, $reportTypeId,
    $name, &$htmlBody) {
  # Create a reporting job with a name and a report type id.
  $reportingJob = new Google_Service_YouTubeReporting_Job();
  $reportingJob->setReportTypeId($reportTypeId);
  $reportingJob->setName($name);

  // Call the YouTube Reporting API's jobs.create method to create a job.
  $jobCreateResponse = $youtubeReporting->jobs->create($reportingJob);

  $htmlBody .= "<h2>Created reporting job</h2><ul>";
  $htmlBody .= sprintf('<li>"%s" for reporting type "%s" at "%s"</li>',
      $jobCreateResponse['name'], $jobCreateResponse['reportTypeId'], $jobCreateResponse['createTime']);
  $htmlBody .= '</ul>';
}


/**
 * Returns a list of report types. (reportTypes.listReportTypes)
 *
 * @param Google_Service_YouTubereporting $youtubeReporting YouTube Reporting service object.
 * @param $htmlBody - html body.
 */
function listReportTypes(Google_Service_YouTubeReporting $youtubeReporting, &$htmlBody) {
  // Call the YouTube Reporting API's reportTypes.list method to retrieve report types.
  $reportTypes = $youtubeReporting->reportTypes->listReportTypes();

  $htmlBody .= "<h3>Report Types</h3><ul>";
  foreach ($reportTypes as $reportType) {
    $htmlBody .= sprintf('<li>id: "%s", name: "%s"</li>', $reportType['id'], $reportType['name']);
  }
  $htmlBody .= '</ul>';

  return $reportTypes;
}
?>

<!doctype html>
<html>
<head>
<title>Create a reporting job</title>
</head>
<body>
  <form method="GET">
    <div>
      Job Name: <input type="text" id="jobName" name="jobName" placeholder="Enter Job Name">
    </div>
    <br>
    <div>
      Report Type Id: <input type="text" id="reportTypeId" name="reportTypeId" placeholder="Enter Report Type Id">
    </div>
    <br>
    <input type="submit" value="Create!">
  </form>
  <?=$htmlBody?>
</body>
</html>
