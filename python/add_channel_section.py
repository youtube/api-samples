#!/usr/bin/python

import httplib2
import os
import re
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

# This variable defines a message to display if the CLIENT_SECRETS_FILE is
# missing.
MISSING_CLIENT_SECRETS_MESSAGE = """
WARNING: Please configure OAuth 2.0

To make this sample run you will need to populate the client_secrets.json file
found at:

   %s

with information from the {{ Cloud Console }}
{{ https://cloud.google.com/console }}

For more information about the client_secrets.json file format, please visit:
https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
""" % os.path.abspath(os.path.join(os.path.dirname(__file__),
                                   CLIENT_SECRETS_FILE))

# This OAuth 2.0 access scope allows for full read/write access to the
# authenticated user's account.
YOUTUBE_SCOPE = "https://www.googleapis.com/auth/youtube"
YOUTUBE_API_SERVICE_NAME = "youtube"
YOUTUBE_API_VERSION = "v3"

SECTION_TYPES = ("allPlaylists", "completedEvents", "likedPlaylists",
  "likes", "liveEvents", "multipleChannels", "multiplePlaylists",
  "popularUploads", "recentActivity", "recentPosts", "recentUploads",
  "singlePlaylist", "upcomingEvents",)
SECTION_STYLES = ("horizontalRow", "verticalList",)

def get_authenticated_service(args):
  flow = flow_from_clientsecrets(CLIENT_SECRETS_FILE, scope=YOUTUBE_SCOPE,
    message=MISSING_CLIENT_SECRETS_MESSAGE)

  storage = Storage("%s-oauth2.json" % sys.argv[0])
  credentials = storage.get()

  if credentials is None or credentials.invalid:
    credentials = run_flow(flow, storage, args)

  return build(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION,
    http=credentials.authorize(httplib2.Http()))

def enable_browse_view(youtube):
  channels_list_response = youtube.channels().list(
    part="brandingSettings",
    mine=True
  ).execute()

  channel = channels_list_response["items"][0]
  channel["brandingSettings"]["channel"]["showBrowseView"] = True

  youtube.channels().update(
    part="brandingSettings",
    body=channel
  ).execute()

def add_channel_section(youtube, args):
  channels = None
  if args.channels:
    channels = re.split("\s*,\s*", args.channels)
  playlists = None
  if args.playlists:
    playlists = re.split("\s*,\s*", args.playlists)

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
    part="snippet,contentDetails",
    body=body
  ).execute()

if __name__ == '__main__':
  argparser.add_argument("--type", choices=SECTION_TYPES, required=True,
    help="The type of the section to be added.")
  argparser.add_argument("--style", choices=SECTION_STYLES, required=True,
    help="The style of the section to be added.")
  argparser.add_argument("--title",
    help=("The title to display for the new section. This is only used "
          "with the multiplePlaylists or multipleChannels section types."))
  argparser.add_argument("--position", type=int,
    help=("The position of the new section. "
         "Use 0 for the top, or don't set a value for the bottom."))
  argparser.add_argument("--playlists",
    help="One or more playlist ids, comma-separated (e.g. PL...).")
  argparser.add_argument("--channels",
    help="One or more channel ids, comma-separated (e.g. UC...).")
  args = argparser.parse_args()

  youtube = get_authenticated_service(args)
  try:
    # Before channel shelves will appear on your channel's web page, browse
    # view needs to be enabled. If you know that your channel already has
    # it enabled, or if you want to add a number of sections before enabling it,
    # you can skip the call to enable_browse_view().
    enable_browse_view(youtube)

    add_channel_section(youtube, args)
  except HttpError, e:
    print "An HTTP error %d occurred:\n%s" % (e.resp.status, e.content)
  else:
    print "Added new channel section."
