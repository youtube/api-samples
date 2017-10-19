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

# This OAuth 2.0 access scope allows for full read/write access to the
# authenticated user's account.
SCOPES = ['https://www.googleapis.com/auth/youtube']
API_SERVICE_NAME = 'youtube'
API_VERSION = 'v3'

SECTION_TYPES = ('allPlaylists', 'completedEvents', 'likedPlaylists',
  'likes', 'liveEvents', 'multipleChannels', 'multiplePlaylists',
  'popularUploads', 'recentActivity', 'recentPosts', 'recentUploads',
  'singlePlaylist', 'upcomingEvents',)
SECTION_STYLES = ('horizontalRow', 'verticalList',)


def get_authenticated_service():
  flow = InstalledAppFlow.from_client_secrets_file(CLIENT_SECRETS_FILE, SCOPES)
  credentials = flow.run_console()
  return build(API_SERVICE_NAME, API_VERSION, credentials = credentials)

def print_response(response):
  print(response)

def enable_browse_view(youtube):
  channels_list_response = youtube.channels().list(
    part='brandingSettings',
    mine=True
  ).execute()

  channel = channels_list_response['items'][0]
  channel['brandingSettings']['channel']['showBrowseView'] = True

  youtube.channels().update(
    part='brandingSettings',
    body=channel
  ).execute()

def add_channel_section(youtube, args):
  channels = None
  if args.channels:
    channels = re.split('\s*,\s*', args.channels)
  playlists = None
  if args.playlists:
    playlists = re.split('\s*,\s*', args.playlists)

  body = dict(
    snippet=dict(
      type=args.type,
      style=args.style,
      title=args.title,
      position=args.position
    ),
    contentDetails=dict(
      channels=channels,
      playlists=playlists
    )
  )

  youtube.channelSections().insert(
    part='snippet,contentDetails',
    body=body
  ).execute()

if __name__ == '__main__':

  parser = argparse.ArgumentParser(description='Process some integers.')
  parser.add_argument('--type', choices=SECTION_TYPES, required=True,
      help='The type of the section to be added.')
  parser.add_argument('--style', choices=SECTION_STYLES, required=True,
      help='The style of the section to be added.')
  parser.add_argument('--title',
      help='The title to display for the new section. This is only used '
           'with the multiplePlaylists or multipleChannels section types.')
  parser.add_argument('--position', type=int,
      help='The position of the new section. Use 0 for the top, '
           'or don\'t set a value for the bottom.')
  parser.add_argument('--playlists',
      help='One or more playlist ids, comma-separated (e.g. PL...).')
  parser.add_argument('--channels',
      help='One or more channel ids, comma-separated (e.g. UC...).')

  args = parser.parse_args()

  youtube = get_authenticated_service()
  try:
    # Before channel shelves will appear on your channel's web page, browse
    # view needs to be enabled. If you know that your channel already has
    # it enabled, or if you want to add a number of sections before enabling it,
    # you can skip the call to enable_browse_view().
    enable_browse_view(youtube)

    add_channel_section(youtube, args)
  except HttpError, e:
    print 'An HTTP error %d occurred:\n%s' % (e.resp.status, e.content)
  else:
    print 'Added new channel section.
