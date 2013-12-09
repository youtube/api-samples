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

# This OAuth 2.0 access scope allows for full read/write access to the
# authenticated user's account.
YOUTUBE_READ_WRITE_SCOPE = "https://www.googleapis.com/auth/youtube"
YOUTUBE_API_SERVICE_NAME = "youtube"
YOUTUBE_API_VERSION = "v3"

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

def get_authenticated_service(args):
  flow = flow_from_clientsecrets(CLIENT_SECRETS_FILE,
    scope=YOUTUBE_READ_WRITE_SCOPE,
    message=MISSING_CLIENT_SECRETS_MESSAGE)

  storage = Storage("%s-oauth2.json" % sys.argv[0])
  credentials = storage.get()

  if credentials is None or credentials.invalid:
    credentials = run_flow(flow, storage, args)

  return build(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION,
    http=credentials.authorize(httplib2.Http()))

# This method calls the API's youtube.activities.insert method to post the
# channel bulletin.
def post_bulletin(youtube, args):
  body = dict(
    snippet=dict(
      description=args.message
    )
  )

  if args.video_id:
    body["contentDetails"] = dict(
      bulletin=dict(
        resourceId=dict(
          kind="youtube#video",
          videoId=args.video_id
        )
      )
    )

  if args.playlist_id:
    body["contentDetails"] = dict(
      bulletin=dict(
        resourceId=dict(
          kind="youtube#playlist",
          playlistId=args.playlist_id
        )
      )
    )

  youtube.activities().insert(
    part=",".join(body.keys()),
    body=body
  ).execute()

if __name__ == "__main__":
  argparser.add_argument("--message", required=True,
    help="Text of message to post.")
  argparser.add_argument("--video-id",
    help="Optional ID of video to post.")
  argparser.add_argument("--playlist-id",
    help="Optional ID of playlist to post.")
  args = argparser.parse_args()

  # You can post a message with or without an accompanying video or playlist.
  # However, you can't post a video and a playlist at the same time.
  if args.video_id and args.playlist_id:
    exit("You cannot post a video and a playlist at the same time.")

  youtube = get_authenticated_service(args)
  try:
    post_bulletin(youtube, args)
  except HttpError, e:
    print "An HTTP error %d occurred:\n%s" % (e.resp.status, e.content)
  else:
    print "The bulletin was posted to your channel."
