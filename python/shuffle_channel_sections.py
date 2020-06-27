#!/usr/bin/python

import os
import random

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

# This OAuth 2.0 access scope allows for full read/write access to the
# authenticated user's account.
SCOPES = ['https://www.googleapis.com/auth/youtube']
API_SERVICE_NAME = 'youtube'
API_VERSION = 'v3'

# Authorize the request and store authorization credentials.
def get_authenticated_service():
  flow = InstalledAppFlow.from_client_secrets_file(CLIENT_SECRETS_FILE, SCOPES)
  credentials = flow.run_console()
  return build(API_SERVICE_NAME, API_VERSION, credentials = credentials)

def get_current_channel_sections(youtube):
  channel_sections_list_response = youtube.channelSections().list(
    part='snippet,contentDetails',
    mine=True
  ).execute()

  return channel_sections_list_response['items']

def shuffle_channel_sections(youtube, channel_sections):
  # This will randomly reorder the items in the channel_sections list.
  random.shuffle(channel_sections)

  for channel_section in channel_sections:
    # Each section in the list of shuffled sections is sequentially
    # set to position 0, i.e. the top.
    channel_section['snippet']['position'] = 0

    youtube.channelSections().update(
      part='snippet,contentDetails',
      body=channel_section
    ).execute()

if __name__ == '__main__':
  youtube = get_authenticated_service()
  try:
    channel_sections = get_current_channel_sections(youtube)
    shuffle_channel_sections(youtube, channel_sections)
  except HttpError as e:
    print('An HTTP error %d occurred:\n%s' % (e.resp.status, e.content))
  else:
    print('The existing channel sections have been randomly shuffled.')
