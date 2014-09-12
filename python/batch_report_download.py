#!/usr/bin/python

import httplib2
import os
import sys
import urllib

from apiclient.discovery import build, build_from_document
from apiclient.errors import HttpError
from apiclient.http import MediaIoBaseDownload
from oauth2client.client import flow_from_clientsecrets
from oauth2client.file import Storage
from oauth2client.tools import argparser, run_flow


# The CLIENT_SECRETS_FILE variable specifies the name of a file that contains
# the OAuth 2.0 information for this application, including its client_id and
# client_secret. You can acquire an OAuth 2.0 client ID and client secret from
# the Google Cloud Console at
# https://cloud.google.com/console.
# Please ensure that you have enabled the YouTube Analytics API for your project.
# For more information about using OAuth2 to access Google APIs, see:
#   https://developers.google.com/youtube/v3/guides/authentication
# For more information about the client_secrets.json file format, see:
#   https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
CLIENT_SECRETS_FILE = "client_secrets.json"

# This OAuth 2.0 access scope allows for full read/write access to the
# authenticated user's account.
SCOPES = ("https://www.googleapis.com/auth/yt-analytics-monetary.readonly",
          "https://www.googleapis.com/auth/yt-analytics.readonly")
YOUTUBE_ANALYTICS_API_SERVICE_NAME = "youtubeAnalytics"
YOUTUBE_ANALYTICS_API_VERSION = "v1beta1"

# This variable defines a message to display if the CLIENT_SECRETS_FILE is
# missing.
MISSING_CLIENT_SECRETS_MESSAGE = """
WARNING: Please configure OAuth 2.0

To make this sample run you will need to populate the client_secrets.json file
found at:

   %s

with information from the Cloud Console
https://cloud.google.com/console

For more information about the client_secrets.json file format, please visit:
https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
""" % os.path.abspath(os.path.join(os.path.dirname(__file__),
                                   CLIENT_SECRETS_FILE))

# Maps a shorthand notation for the different report types to the string used
# to identify each type in the batchReportDefinitionList response.
REPORT_TYPES_TO_NAMES = dict(
  assets="Full asset report",
  claims="Full claim report"
)

def get_authenticated_service(args):
  flow = flow_from_clientsecrets(CLIENT_SECRETS_FILE,
    scope=" ".join(SCOPES),
    message=MISSING_CLIENT_SECRETS_MESSAGE)

  storage = Storage("%s-oauth2.json" % sys.argv[0])
  credentials = storage.get()

  if credentials is None or credentials.invalid:
    credentials = run_flow(flow, storage, args)

  http = credentials.authorize(httplib2.Http())

  return build(YOUTUBE_ANALYTICS_API_SERVICE_NAME,
    YOUTUBE_ANALYTICS_API_VERSION, http=http)

def get_available_reports(youtubeAnalytics, contentOwner):
  definitions_list_response = youtubeAnalytics.batchReportDefinitions().list(
    onBehalfOfContentOwner=contentOwner,
  ).execute()

  return definitions_list_response["items"]

def get_info_for_report(youtubeAnalytics, contentOwner, report_id):
  reports_list_response = youtubeAnalytics.batchReports().list(
    onBehalfOfContentOwner=contentOwner,
    batchReportDefinitionId=report_id
  ).execute()

  url = reports_list_response["items"][0]["outputs"][0]["downloadUrl"]
  date = reports_list_response["items"][0]["timeSpan"]["startTime"]
  return (url, date)

if __name__ == "__main__":
  argparser.add_argument("--content-owner-id", required=True,
    help="ID of the content owner.")
  argparser.add_argument("--report-type", required=True,
    choices=REPORT_TYPES_TO_NAMES.keys(), help="The type of report to download.")
  argparser.add_argument("--download-directory", default=os.getcwd(),
    help="The directory to download the report into.")
  args = argparser.parse_args()

  # Steps to download a batch report:
  # 1. Given an authorized instance of the YouTube Analytics service, retrieve
  #    a list of all the available report definitions via
  #    youtubeAnalytics.batchReportDefinitions.list()
  # 2. Iterate through the report definitions to find the one we're interested
  #    in based on its name: either an assets or claims report.
  # 3. Get the unique id of the report definition, which will in turn be passed
  #    in to youtubeAnalytics.batchReports.list().
  # 4. The youtubeAnalytics.batchReports.list() reponse will contain one or more
  #    days' worth of reports. The code gets download info for the first item
  #    in the response, which will be the most recent day's report.
  # 5. Parse out the date and the download URL for the relevant report, and use
  #    that to download the report, with the date used as part of the file name.
  youtubeAnalytics = get_authenticated_service(args)
  try:
    reports = get_available_reports(youtubeAnalytics, args.content_owner_id)

    report_id = None
    for report in reports:
      if (REPORT_TYPES_TO_NAMES[args.report_type] == report["name"]
        and report["status"] == "supported"):
        report_id = report["id"]
        break

    if report_id:
      (url, date) = get_info_for_report(youtubeAnalytics,
        args.content_owner_id, report_id)

      file_path = os.path.join(args.download_directory,
        "%s-%s.csv" % (args.report_type, date))

      # This is a simple approach to downloading a file at a given URL.
      # If desired, you can add in a callback method to log the
      # progress of the download. See
      # http://docs.python.org/2/library/urllib.html#urllib.urlretrieve
      urllib.urlretrieve(url, file_path)

      print "The report was downloaded to %s" % file_path
    else:
      # There might not be a report available if, for instance, there are no
      # assets or claims associated with a given content owner's account.
      print "No report of type '%s' was available." % args.report_type
  except HttpError, e:
    print "An HTTP error %d occurred:\n%s" % (e.resp.status, e.content)
