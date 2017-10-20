#!/usr/bin/python

# This sample shows how to rate a video.
# Sample usage:
#   python like_video.py --videoId=OE63BYWdqC4 --rating=like

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
SCOPES = ['https://www.googleapis.com/auth/youtube.force-ssl']
API_SERVICE_NAME = 'youtube'
API_VERSION = 'v3'

RATINGS = ('like', 'dislike', 'none')

# Authorize the request and store authorization credentials.
def get_authenticated_service():
  flow = InstalledAppFlow.from_client_secrets_file(CLIENT_SECRETS_FILE, SCOPES)
  credentials = flow.run_console()
  return build(API_SERVICE_NAME, API_VERSION, credentials = credentials)

# Add the video rating. This code sets the rating to 'like,' but you could
# also support an additional option that supports values of 'like' and
# 'dislike.'
def like_video(youtube, args):
  youtube.videos().rate(
    id=args.videoId,
    rating=args.rating
  ).execute()

if __name__ == '__main__':
  parser = argparse.ArgumentParser()
  parser.add_argument('--videoId', default='OE63BYWdqC4',
    help='ID of video to like.')
  parser.add_argument('--rating', default='like',
    choices=RATINGS,
    help='Indicates whether the rating is "like", "dislike", or "none".')
  args = parser.parse_args()

  youtube = get_authenticated_service()
  try:
    like_video(youtube, args)
  except HttpError, e:
    print 'An HTTP error %d occurred:\n%s' % (e.resp.status, e.content)
  else:
    print ('The %s rating has been added for video ID %s.' %
           (args.rating, args.videoId))
