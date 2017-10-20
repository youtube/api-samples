#!/usr/bin/python

import argparse
import os
import re

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

# This OAuth 2.0 access scope allows for read-only access to the authenticated
# user's account, but not other types of account access.
SCOPES = ['https://www.googleapis.com/auth/youtube.readonly']
API_SERVICE_NAME = 'youtube'
API_VERSION = 'v3'

VALID_BROADCAST_STATUSES = ('all', 'active', 'completed', 'upcoming',)

# Authorize the request and store authorization credentials.
def get_authenticated_service():
  flow = InstalledAppFlow.from_client_secrets_file(CLIENT_SECRETS_FILE, SCOPES)
  credentials = flow.run_console()
  return build(API_SERVICE_NAME, API_VERSION, credentials = credentials)

# Retrieve a list of broadcasts with the specified status.
def list_broadcasts(youtube, broadcast_status):
  print 'Broadcasts with status "%s":' % broadcast_status

  list_broadcasts_request = youtube.liveBroadcasts().list(
    broadcastStatus=broadcast_status,
    part='id,snippet',
    maxResults=50
  )

  while list_broadcasts_request:
    list_broadcasts_response = list_broadcasts_request.execute()

    for broadcast in list_broadcasts_response.get('items', []):
      print '%s (%s)' % (broadcast['snippet']['title'], broadcast['id'])

    list_broadcasts_request = youtube.liveBroadcasts().list_next(
      list_broadcasts_request, list_broadcasts_response)

if __name__ == '__main__':
  parser = argparse.ArgumentParser()
  parser.add_argument('--broadcast-status', help='Broadcast status',
    choices=VALID_BROADCAST_STATUSES, default=VALID_BROADCAST_STATUSES[0])
  args = parser.parse_args()

  youtube = get_authenticated_service()
  try:
    list_broadcasts(youtube, args.broadcast_status)
  except HttpError, e:
    print 'An HTTP error %d occurred:\n%s' % (e.resp.status, e.content)
