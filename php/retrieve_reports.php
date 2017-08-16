<?php

/**
 * This sample supports the following use cases:
 *
 * 1. Retrieve reporting jobs by content owner:
 *    Ex: php retrieve_reports.php  --contentOwner=="CONTENT_OWNER_ID"
 *    Ex: php retrieve_reports.php  --contentOwner=="CONTENT_OWNER_ID" --includeSystemManaged==True
 * 2. Retrieving list of downloadable reports for a particular job:
 *    Ex: php retrieve_reports.php  --contentOwner=="CONTENT_OWNER_ID" --jobId="JOB_ID"
 * 3. Download a report:
 *    Ex: php retrieve_reports.php  --contentOwner=="CONTENT_OWNER_ID" --downloadUrl="DOWNLOAD_URL" --outputFile="report.txt"
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
  throw new \Exception('please run "composer require google/apiclient:~2.2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';
session_start();


define('CREDENTIALS_PATH', '~/.credentials/youtube-php.json');

$longOptions = array(
  'contentOwner::',
  'downloadUrl::',
  'includeSystemManaged::',
  'jobId::',
  'outputFile::',
);

$options = getopt('', $longOptions);

$CONTENT_OWNER_ID = ($options['contentOwner'] ? $options['contentOwner'] : '');
$DOWNLOAD_URL = (array_key_exists('downloadUrl', $options) ?
                 $options['downloadUrl'] : '');
$INCLUDE_SYSTEM_MANAGED = (array_key_exists('includeSystemManaged', $options) ?
                           $options['includeSystemManaged'] : '');
$JOB_ID = (array_key_exists('jobId', $options) ? $options['jobId'] : '');
$OUTPUT_FILE = (array_key_exists('outputFile', $options) ?
                $options['outputFile'] : '');

/*
 * You can obtain an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
function getClient() {
  $client = new Google_Client();
  $client->setAuthConfigFile('client_secrets_php.json');
  $client->addScope(
      'https://www.googleapis.com/auth/yt-analytics-monetary.readonly');
  $client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf('Open the following link in your browser:\n%s\n', $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->authenticate($authCode);
    $refreshToken = $client->getRefreshToken();

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, json_encode($accessToken));
    printf('Credentials saved to %s\n', $credentialsPath);

    //fclose($fp);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
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
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

/**
 * Returns a list of reporting jobs. (jobs.listJobs)
 *
 * @param Google_Service_YouTubereporting $youtubeReporting YouTube Reporting service object.
 * @param string $onBehalfOfContentOwner A content owner ID.
 */
function listReportingJobs(Google_Service_YouTubeReporting $youtubeReporting,
    $onBehalfOfContentOwner = '', $includeSystemManaged = False) {
  $reportingJobs = $youtubeReporting->jobs->listJobs(
      array('onBehalfOfContentOwner' => $onBehalfOfContentOwner,
            'includeSystemManaged' => $includeSystemManaged));
  print ('REPORTING JOBS' . PHP_EOL . '**************' . PHP_EOL);
  foreach ($reportingJobs as $job) {
    print($job['reportTypeId'] . ':' . $job['id'] . PHP_EOL);
  }
  print(PHP_EOL);
}

/**
 * Lists reports created by a specific job. (reports.listJobsReports)
 *
 * @param Google_Service_YouTubereporting $youtubeReporting YouTube Reporting service object.
 * @param string $jobId The ID of the job.
 * @param string $onBehalfOfContentOwner A content owner ID.
 */
function listReportsForJob(Google_Service_YouTubeReporting $youtubeReporting,
    $jobId, $onBehalfOfContentOwner = '') {
  $reports = $youtubeReporting->jobs_reports->listJobsReports($jobId,
      array('onBehalfOfContentOwner' => $onBehalfOfContentOwner));
  print ('DOWNLOADABLE REPORTS' . PHP_EOL . '********************' . PHP_EOL);
  foreach ($reports['reports'] as $report) {
    print('Created: ' . date('d M Y', strtotime($report['createTime'])) .
          ' (' . date('d M Y', strtotime($report['startTime'])) .
          ' to ' . date('d M Y', strtotime($report['endTime'])) . ')' .
          PHP_EOL .  '    ' . $report['downloadUrl'] . PHP_EOL . PHP_EOL);
  }
}

/**
 * Download the report specified by the URL. (media.download)
 *
 * @param Google_Service_YouTubereporting $youtubeReporting YouTube Reporting service object.
 * @param string $reportUrl The URL of the report to be downloaded.
 * @param string $outputFile The file to write the report to locally.
 * @param $htmlBody - html body.
 */
function downloadReport(Google_Service_YouTubeReporting $youtubeReporting,
    $reportUrl, $outputFile) {
  $client = $youtubeReporting->getClient();
  // Setting the defer flag to true tells the client to return a request that
  // can be called with ->execute(); instead of making the API call immediately.
  $client->setDefer(true);

  // Call YouTube Reporting API's media.download method to download a report.
  $request = $youtubeReporting->media->download('', array('alt' => 'media'));
  $request = $request->withUri(new \GuzzleHttp\Psr7\Uri($reportUrl));
  $responseBody = '';
  try {
    $response = $client->execute($request);
    $responseBody = $response->getBody();
  } catch (Google_Service_Exception $e) {
    $responseBody = $e->getTrace()[0]['args'][0]->getResponseBody();
  }
  file_put_contents($outputFile, $responseBody);
  $client->setDefer(false);
}

// Define an object that will be used to make all API requests.
$client = getClient();
// YouTube Reporting object used to make YouTube Reporting API requests.
$youtubeReporting = new Google_Service_YouTubeReporting($client);

if ($CONTENT_OWNER_ID) {
  if (!$DOWNLOAD_URL && !$JOB_ID) {
    listReportingJobs($youtubeReporting, $CONTENT_OWNER_ID,
                      $INCLUDE_SYSTEM_MANAGED);
  } else if ($JOB_ID) {
    listReportsForJob($youtubeReporting, $JOB_ID, $CONTENT_OWNER_ID);
  } else if ($DOWNLOAD_URL && $OUTPUT_FILE) {
    downloadReport($youtubeReporting, $DOWNLOAD_URL, $OUTPUT_FILE);
  }
}

?>
