<?php

/**
 * This sample retrieves reports created by a specific job by:
 *
 * 1. Listing the jobs using the "jobs.list" method.
 * 2. Retrieving reports using the "reports.list" method.
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
 * This OAuth 2.0 access scope allows for full read/write access to the
 * authenticated user's account.
 */
$client->setScopes('https://www.googleapis.com/auth/yt-analytics-monetary.readonly');
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// YouTube Reporting object used to make YouTube Reporting API requests.
$youtubeReporting = new Google_Service_YoutubeReporting($client);

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
  try {
    if (empty(listReportingJobs($youtubeReporting, $htmlBody))) {
      $htmlBody .= sprintf('<p>No jobs found.</p>');
    } else if ($_GET['reportUrl']){
      downloadReport($youtubeReporting, $_GET['reportUrl'], $htmlBody);
    } else if ($_GET['jobId']){
      retrieveReports($youtubeReporting, $_GET['jobId'], $htmlBody);
    }
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


/**
 * Returns a list of reporting jobs. (jobs.listJobs)
 *
 * @param Google_Service_YouTubereporting $youtubeReporting YouTube Reporting service object.
 * @param $htmlBody - html body.
 */
function listReportingJobs(Google_Service_YouTubeReporting $youtubeReporting, &$htmlBody) {
  // Call the YouTube Reporting API's jobs.list method to retrieve reporting jobs.
  $reportingJobs = $youtubeReporting->jobs->listJobs();

  $htmlBody .= "<h3>Reporting Jobs</h3><ul>";
  foreach ($reportingJobs as $job) {
    $htmlBody .= sprintf('<li>id: "%s", name: "%s" report type: "%s"</li>', $job['id'],
        $job['name'], $job['reportTypeId']);
  }
  $htmlBody .= '</ul>';

  return $reportingJobs;
}


/**
 * Lists reports created by a specific job. (reports.listJobsReports)
 *
 * @param Google_Service_YouTubereporting $youtubeReporting YouTube Reporting service object.
 * @param string $jobId The ID of the job.
 * @param $htmlBody - html body.
 */
function retrieveReports(Google_Service_YouTubeReporting $youtubeReporting, $jobId, &$htmlBody) {
  // Call the YouTube Reporting API's reports.list method to retrieve reports created by a job.
  $reports = $youtubeReporting->jobs_reports->listJobsReports($jobId);

  if (empty($reports)) {
    $htmlBody .= sprintf('<p>No reports found.</p>');
  } else {
    $htmlBody .= sprintf('<h2>Reports for the job "%s"</h2><ul>', $jobId);
    foreach ($reports as $report) {
      $htmlBody .= sprintf('<li>From "%s" to "%s" downloadable at "%s"</li>',
          $report['startTime'], $report['endTime'], $report['downloadUrl']);
      $htmlBody .= '</ul>';
    }
  }
}


/**
 * Download the report specified by the URL. (media.download)
 *
 * @param Google_Service_YouTubereporting $youtubeReporting YouTube Reporting service object.
 * @param string $reportUrl The URL of the report to be downloaded.
 * @param $htmlBody - html body.
 */
function downloadReport(Google_Service_YouTubeReporting $youtubeReporting, $reportUrl, &$htmlBody) {
  $client = $youtubeReporting->getClient();
  // Setting the defer flag to true tells the client to return a request which can be called
  // with ->execute(); instead of making the API call immediately.
  $client->setDefer(true);

  // Call the YouTube Reporting API's media.download method to download a report.
  $request = $youtubeReporting->media->download("");
  $request->setUrl($reportUrl);
  $response = $client->execute($request);

  file_put_contents("reportFile", $response->getResponseBody());
  $client->setDefer(false);
}
?>

<!doctype html>
<html>
<head>
<title>Retrieve reports</title>
</head>
<body>
  <form method="GET">
    <div>
      Job Id: <input type="text" id="jobId" name="jobId" placeholder="Enter Job Id">
    </div>
    <br>
    <div>
      Report URL: <input type="text" id="reportUrl" name="reportUrl" placeholder="Enter Report Url">
    </div>
    <br>    <input type="submit" value="Retrieve!">
  </form>
  <?=$htmlBody?>
</body>
</html>
