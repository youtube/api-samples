#!/usr/bin/python

# Usage example:
# python retrieve_reports.py

import httplib2
import os
import sys

from apiclient.discovery import build
from apiclient.errors import HttpError
from apiclient.http import MediaIoBaseDownload
from io import FileIO
from oauth2client.client import flow_from_clientsecrets
from oauth2client.file import Storage
from oauth2client.tools import argparser, run_flow


# The CLIENT_SECRETS_FILE variable specifies the name of a file that contains

# the OAuth 2.0 information for this application, including its client_id and
# client_secret. You can acquire an OAuth 2.0 client ID and client secret from
# the {{ Google Cloud Console }} at
# {{ https://cloud.google.com/console }}.
# Please ensure that you have enabled the YouTube Data API for your project.
# For more information about using OAuth2 to access the YouTube Data API, see:
#   https://developers.google.com/youtube/v3/guides/authentication
# For more information about the client_secrets.json file format, see:
#   https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
CLIENT_SECRETS_FILE = "client_secrets.json"

# This OAuth 2.0 access scope allows for read access to the YouTube Analytics monetary reports for
# authenticated user's account. Any request that retrieves earnings or ad performance metrics must
# use this scope.
YOUTUBE_ANALYTICS_MONETARY_READ_SCOPE = (
  "https://www.googleapis.com/auth/yt-analytics-monetary.readonly")
YOUTUBE_REPORTING_API_SERVICE_NAME = "youtubereporting"
YOUTUBE_REPORTING_API_VERSION = "v1"

# This variable defines a message to display if the CLIENT_SECRETS_FILE is
# missing.
MISSING_CLIENT_SECRETS_MESSAGE = """
WARNING: Please configure OAuth 2.0

To make this sample run you will need to populate the client_secrets.json file
found at:
   %s
with information from the APIs Console
https://console.developers.google.com

For more information about the client_secrets.json file format, please visit:
https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
""" % os.path.abspath(os.path.join(os.path.dirname(__file__),
                                   CLIENT_SECRETS_FILE))

# Authorize the request and store authorization credentials.
def get_authenticated_service(args):
  flow = flow_from_clientsecrets(CLIENT_SECRETS_FILE, scope=YOUTUBE_ANALYTICS_MONETARY_READ_SCOPE,
    message=MISSING_CLIENT_SECRETS_MESSAGE)

  storage = Storage("%s-oauth2.json" % sys.argv[0])
  credentials = storage.get()

  if credentials is None or credentials.invalid:
    credentials = run_flow(flow, storage, args)

  return build(YOUTUBE_REPORTING_API_SERVICE_NAME, YOUTUBE_REPORTING_API_VERSION,
    http=credentials.authorize(httplib2.Http()))


# Call the YouTube Reporting API's jobs.list method to retrieve reporting jobs.
def list_reporting_jobs(youtube_reporting):
  results = youtube_reporting.jobs().list(
  ).execute()

  if "jobs" in results and results["jobs"]:
    jobs = results["jobs"]
    for job in jobs:
      print ("Reporting job id: %s\n name: %s\n for reporting type: %s\n"
        % (job["id"], job["name"], job["reportTypeId"]))
  else:
    print "No jobs found"
    return False

  return True


# Call the YouTube Reporting API's reports.list method to retrieve reports created by a job.
def retrieve_reports(youtube_reporting, job_id):
  results = youtube_reporting.jobs().reports().list(
    jobId=job_id
  ).execute()

  if "reports" in results and results["reports"]:
    reports = results["reports"]
    for report in reports:
      print ("Report from '%s' to '%s' downloadable at '%s'"
        % (report["startTime"], report["endTime"], report["downloadUrl"]))


# Call the YouTube Reporting API's media.download method to download the report.
def download_report(youtube_reporting, report_url):
  request = youtube_reporting.media().download(
    resourceName=""
  )
  request.uri = report_url
  fh = FileIO('report', mode='wb')
  # Stream/download the report in a single request.
  downloader = MediaIoBaseDownload(fh, request, chunksize=-1)

  done = False
  while done is False:
    status, done = downloader.next_chunk()
    if status:
      print "Download %d%%." % int(status.progress() * 100)
  print "Download Complete!"


# Prompt the user to enter a job id for report retrieval. Then return the id.
def get_job_id_from_user():
  job_id = raw_input("Please enter the job id for the report retrieval: ")
  print ("You chose '%s' as the job Id for the report retrieval." % job_id)
  return job_id


# Prompt the user to enter a report URL for download. Then return the URL.
def get_report_url_from_user():
  report_url = raw_input("Please enter the report URL to download: ")
  print ("You chose '%s' to download." % report_url)
  return report_url

if __name__ == "__main__":
  args = argparser.parse_args()

  youtube_reporting = get_authenticated_service(args)
  try:
    if list_reporting_jobs(youtube_reporting):
      retrieve_reports(youtube_reporting, get_job_id_from_user())
      download_report(youtube_reporting, get_report_url_from_user())
  except HttpError, e:
    print "An HTTP error %d occurred:\n%s" % (e.resp.status, e.content)
  else:
    print "Retrieved reports."
