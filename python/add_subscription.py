#!/usr/bin/python

# This code sample shows how to add a channel subscription.
# The default channel-id is for the GoogleDevelopers YouTube channel.
# Sample usage:
# python add_subscription.py --channel-id=UC_x5XG1OV2P6uZZ5FSM9Ttw

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

# This OAuth 2.0 access scope allows for full read/write access to the
# authenticated user's account.
SCOPES = ['https://www.googleapis.com/auth/youtube']
API_SERVICE_NAME = 'youtube'
API_VERSION = 'v3'

def get_authenticated_service():
  flow = InstalledAppFlow.from_client_secrets_file(CLIENT_SECRETS_FILE, SCOPES)
  credentials = flow.run_console()
  return build(API_SERVICE_NAME, API_VERSION, credentials = credentials)

# This method calls the API's youtube.subscriptions.insert method to add a
# subscription to the specified channel.
def add_subscription(youtube, channel_id):
  add_subscription_response = youtube.subscriptions().insert(
    part='snippet',
    body=dict(
      snippet=dict(
        resourceId=dict(
          channelId=channel_id
        )
      )
    )).execute()

  return add_subscription_response['snippet']['title']

if __name__ == '__main__':
  parser = argparse.ArgumentParser(description='Process arguments.')
  parser.add_argument('--channel-id', help='ID of the channel to subscribe to.',
    default='UC_x5XG1OV2P6uZZ5FSM9Ttw')
  args = parser.parse_args()

  youtube = get_authenticated_service()
  try:
    channel_title = add_subscription(youtube, args.channel_id)
  except HttpError, e:
    print 'An HTTP error %d occurred:\n%s' % (e.resp.status, e.content)
  else:
    print 'A subscription to \'%s\' was added.' % channel_title
