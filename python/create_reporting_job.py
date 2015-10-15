#!/usr/bin/python

# Usage example:
# python create_reporting_job.py --name='<name>'

import httplib2
import os
import sys

from apiclient.discovery import build
from apiclient.errors import HttpError
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


# Call the YouTube Reporting API's reportTypes.list method to retrieve report types.
def list_report_types(youtube_reporting):
  results = youtube_reporting.reportTypes().list().execute()
  reportTypes = results["reportTypes"]

  if "reportTypes" in results and results["reportTypes"]:
    reportTypes = results["reportTypes"]
    for reportType in reportTypes:
      print "Report type id: %s\n name: %s\n" % (reportType["id"], reportType["name"])
  else:
    print "No report types found"
    return False

  return True


# Call the YouTube Reporting API's jobs.create method to create a job.
def create_reporting_job(youtube_reporting, report_type_id, name):
  reporting_job = youtube_reporting.jobs().create(
    body=dict(
      reportTypeId=report_type_id,
      name=name
    )
  ).execute()

  print ("Reporting job '%s' created for reporting type '%s' at '%s'"
         % (reporting_job["name"], reporting_job["reportTypeId"],
             reporting_job["createTime"]))


# Prompt the user to enter a report type id for the job. Then return the id.
def get_report_type_id_from_user():
  report_type_id = raw_input("Please enter the reportTypeId for the job: ")
  print ("You chose '%s' as the report type Id for the job." % report_type_id)
  return report_type_id


if __name__ == "__main__":
  # The "name" option specifies the name that will be used for the reporting job.
  argparser.add_argument("--name",
    help="Required; name for the reporting job.")
  args = argparser.parse_args()

  if not args.name:
    exit("Please specify name using the --name= parameter.")

  youtube_reporting = get_authenticated_service(args)
  try:
    if list_report_types(youtube_reporting):
      create_reporting_job(youtube_reporting, get_report_type_id_from_user(), args.name)
  except HttpError, e:
    print "An HTTP error %d occurred:\n%s" % (e.resp.status, e.content)
  else:
    print "Created reporting job."
