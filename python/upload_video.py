import httplib2
import os
import random
import sys
import time
import google.oauth2.credentials
import google_auth_oauthlib.flow
import argparse 
import http


from googleapiclient.discovery import build
from googleapiclient.errors import HttpError
from googleapiclient.http import MediaFileUpload
from google_auth_oauthlib.flow import InstalledAppFlow

# tell HTTP  library not to retry becasue we are handling the logic ourselves
httplib2.RETRIES=1

# Maximum number of times to retry before giving up
MAX_RETRIES = 10 

# Always retry when these errors are raised
RETRIABLE_EXCEPTIONS = (httplib2.HttpLib2Error, IOError, http.client.NotConnected,
  http.client.IncompleteRead, http.client.ImproperConnectionState,
  http.client.CannotSendRequest, http.client.CannotSendHeader,
  http.client.ResponseNotReady, http.client.BadStatusLine)

# Always retry when an apiclient.errors.HttpError with one of these statuses is raised
RETRIABLE_STATUS_CODES = [500,502,503,504]

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
CLIENT_SECRETS_FILE = 'client_secret_console.json'

# This OAuth 2.0 access scope allows an application to upload files to the
# authenticated user's YouTube Channel, but it doesn't allow other types of access
SCOPES = ['https://www.googleapis.com/auth/youtube.upload']
API_SERVICE_NAME = 'youtube'
API_VERSION = 'v3'

VALID_PRIVACY_STATUSES = ('public','private', 'unlisted')

#authentication flow
def get_service():
  flow = InstalledAppFlow.from_client_secrets_file(CLIENT_SECRETS_FILE, SCOPES)
  credentials = flow.run_console()
  return build(API_SERVICE_NAME, API_VERSION, credentials = credentials)

#Resumable upload function
def resumable_upload(request):
  response = None
  error = None
  retry = 0
  while response is None:
    try:
      print('Uploading file...')
      status, response = request.next_chunk()
      if response is not None:
        if 'id' in response:
          print('Video ID "%s" was successfully uploaded.'% response['id'])
        else:
          exit("The upload failed with an unexpected response: %s" % response)
    except HttpError as e:
      if e.resp.status in RETRIABLE_STATUS_CODES:
        error = 'A retriable HTTP error %d occured: \n%s' % e
      else:
        raise
    except RETRIABLE_EXCEPTIONS as e:
      error = 'A retriable error occurred: %s' % e
    if error is not None:
      print(error)
      retry +=1
      if retry > MAX_RETRIES:
        exit('No longer attempting to retry.')
      max_sleep = 2 ** retry
      sleep_seconds = random.random()*max_sleep
      print('sleeping %f seconds and then retrying...' % sleep_seconds)

# this function creates the request and initializes the upload 
def intialize_upload(youtube, options):
  tags = None
  if options.keywords:
    tags = options.keywords.split(',')
  body = {
    "snippet" : {
      "title":options.title,
      "description":options.description,
      "tags":tags,
      "categoryId":options.category
    },
    "status" : {
      "privacyStatus":options.privacyStatus,
      "selfDeclaredMadeForKids":"false"
    }
  }
  insert_request = youtube.videos().insert(part=','.join(body.keys()),body=body,media_body=MediaFileUpload(options.file,chunksize=-1,resumable=True))
  resumable_upload(insert_request)

#function when run directly
def main():
  # Disable OAUTHlib's https verification locally. DO NOT USE in production.
  os.environ['OUATHLIB_INSECURE_TRANSPORT'] = '1'
  parser = argparse.ArgumentParser()
  parser.add_argument('--file', required=True, help='Video file to upload')
  parser.add_argument('--title', help='Video title', default='Test Title')
  parser.add_argument('--description', help='Video description',
    default='Test Description')
  parser.add_argument('--category', default='22',
    help='Numeric video category. ' +
      'See https://developers.google.com/youtube/v3/docs/videoCategories/list')
  parser.add_argument('--keywords', help='Video keywords, comma separated',
    default='')
  parser.add_argument('--privacyStatus', choices=VALID_PRIVACY_STATUSES,
    default='private', help='Video privacy status.')
  args = parser.parse_args()
  youtubedata = get_service()
  intialize_upload(youtubedata, args)


if __name__ == '__main__':
    main()
