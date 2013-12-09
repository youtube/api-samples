#!/usr/bin/python

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

# If offsetMs is not valid, the API will throw an error
VALID_OFFSET_TYPES = ("offsetFromEnd", "offsetFromStart",)

def get_authenticated_service(args):
  flow = flow_from_clientsecrets(CLIENT_SECRETS_FILE, scope=YOUTUBE_SCOPE,
    message=MISSING_CLIENT_SECRETS_MESSAGE)

  storage = Storage("%s-oauth2.json" % sys.argv[0])
  credentials = storage.get()

  if credentials is None or credentials.invalid:
    credentials = run_flow(flow, storage, args)

  return build(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION,
    http=credentials.authorize(httplib2.Http()))

def add_featured_video(youtube, options):
  add_video_request = youtube.channels().update(
    part="invideoPromotion",
    # You can use the API Explorer to test API requests:
    #    https://developers.google.com/youtube/v3/docs/channels/update#try-it
    body={
      "invideoPromotion": {
        "items": [{
          "id": {
            "type": "video",
            "videoId": options.video_id
          },
          "timing": {
            "offsetMs": options.offset_ms,
            "type": options.offset_type
          }
        }],
      },
      "id": options.channel_id
  }).execute()

if __name__ == '__main__':
  argparser.add_argument("--channel-id", required=True,
    help="Channel ID of the channel to add a featured video")
  argparser.add_argument("--video-id",  required=True,
    help="Video ID to feature on your channel")
  argparser.add_argument("--offset-ms",
    help="Offset in milliseconds to show video.",
    default="10000")
  argparser.add_argument("--offset-type", choices=VALID_OFFSET_TYPES,
    help="Whether the offset is from the beginning or end of video playback.",
    default=VALID_OFFSET_TYPES[0])
  args = argparser.parse_args()

  youtube = get_authenticated_service(args)
  try:
    add_featured_video(youtube, args)
  except HttpError, e:
    print "An HTTP error %d occurred:\n%s" % (e.resp.status, e.content)
  else:
    print "Added featured video %s to channel %s." % (
      args.video_id, args.channel_id)
