#!/usr/bin/python

# Create a reporting job for the authenticated user's channel or
# for a content owner that the user's account is linked to.
# Usage example:
# python create_reporting_job.py --name='<name>'
# python create_reporting_job.py --content-owner='<CONTENT OWNER ID>'
# python create_reporting_job.py --content-owner='<CONTENT_OWNER_ID>' --report-type='<REPORT_TYPE_ID>' --name='<REPORT_NAME>'

import argparse
import os

import google.oauth2.credentials
import google_auth_oauthlib.flow
from googleapiclient.discovery import build
from googleapiclient.errors import HttpError
from google_auth_oauthlib.flow import InstalledAppFlow


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
CLIENT_SECRETS_FILE = 'client_secret.json'

# This OAuth 2.0 access scope allows for read access to the YouTube Analytics monetary reports for
# authenticated user's account. Any request that retrieves earnings or ad performance metrics must
# use this scope.
SCOPES = ['https://www.googleapis.com/auth/yt-analytics-monetary.readonly']
API_SERVICE_NAME = 'youtubereporting'
API_VERSION = 'v1'

# Authorize the request and store authorization credentials.
def get_authenticated_service():
  flow = InstalledAppFlow.from_client_secrets_file(CLIENT_SECRETS_FILE, SCOPES)
  credentials = flow.run_console()
  return build(API_SERVICE_NAME, API_VERSION, credentials = credentials)

# Remove keyword arguments that are not set.
def remove_empty_kwargs(**kwargs):
  good_kwargs = {}
  if kwargs is not None:
    for key, value in kwargs.iteritems():
      if value:
        good_kwargs[key] = value
  return good_kwargs

# Call the YouTube Reporting API's reportTypes.list method to retrieve report types.
def list_report_types(youtube_reporting, **kwargs):
  # Provide keyword arguments that have values as request parameters.
  kwargs = remove_empty_kwargs(**kwargs)
  results = youtube_reporting.reportTypes().list(**kwargs).execute()
  reportTypes = results['reportTypes']

  if 'reportTypes' in results and results['reportTypes']:
    reportTypes = results['reportTypes']
    for reportType in reportTypes:
      print 'Report type id: %s\n name: %s\n' % (reportType['id'], reportType['name'])
  else:
    print 'No report types found'
    return False

  return True


# Call the YouTube Reporting API's jobs.create method to create a job.
def create_reporting_job(youtube_reporting, report_type_id, **kwargs):
  # Provide keyword arguments that have values as request parameters.
  kwargs = remove_empty_kwargs(**kwargs)

  reporting_job = youtube_reporting.jobs().create(
    body=dict(
      reportTypeId=args.report_type,
      name=args.name
    ),
    **kwargs
  ).execute()

  print ('Reporting job "%s" created for reporting type "%s" at "%s"'
         % (reporting_job['name'], reporting_job['reportTypeId'],
             reporting_job['createTime']))


# Prompt the user to enter a report type id for the job. Then return the id.
def get_report_type_id_from_user():
  report_type_id = raw_input('Please enter the reportTypeId for the job: ')
  print ('You chose "%s" as the report type Id for the job.' % report_type_id)
  return report_type_id

# Prompt the user to set a job name
def prompt_user_to_set_job_name():
  job_name = raw_input('Please set a name for the job: ')
  print ('Great! "%s" is a memorable name for this job.' % job_name)
  return job_name


if __name__ == '__main__':
  parser = argparse.ArgumentParser()
  # The 'name' option specifies the name that will be used for the reporting job.
  parser.add_argument('--content-owner', default='',
      help='ID of content owner for which you are retrieving jobs and reports.')
  parser.add_argument('--include-system-managed', default=False,
      help='Whether the API response should include system-managed reports')
  parser.add_argument('--name', default='',
    help='Name for the reporting job. The script prompts you to set a name ' +
         'for the job if you do not provide one using this argument.')
  parser.add_argument('--report-type', default=None,
    help='The type of report for which you are creating a job.')
  args = parser.parse_args()

  youtube_reporting = get_authenticated_service()

  try:
    # Prompt user to select report type if they didn't set one on command line.
    if not args.report_type:
      if list_report_types(youtube_reporting,
                           onBehalfOfContentOwner=args.content_owner,
                           includeSystemManaged=args.include_system_managed):
        args.report_type = get_report_type_id_from_user()
    # Prompt user to set job name if not set on command line.
    if not args.name:
      args.name = prompt_user_to_set_job_name()
    # Create the job.
    if args.report_type:
      create_reporting_job(youtube_reporting,
                           args,
                           onBehalfOfContentOwner=args.content_owner)
  except HttpError, e:
    print 'An HTTP error %d occurred:\n%s' % (e.resp.status, e.content)
