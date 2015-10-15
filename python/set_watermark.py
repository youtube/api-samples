#!/usr/bin/python

# Usage example:
# python set_watermark.py --channelid='<channel_id>' --file='<file_name>'
#  --metadata='{ "position": { "type": "corner", "cornerPosition": "topRight" },
#                "timing": { "type": "offsetFromStart", "offsetMs": 42 } }'

import json
import os
import sys
import httplib2

from apiclient.discovery import build
from apiclient.errors import HttpError
from oauth2client.file import Storage
from oauth2client.client import flow_from_clientsecrets
from oauth2client.tools import argparser, run_flow


# The CLIENT_SECRETS_FILE variable specifies the name of a file that contains

# the OAuth 2.0 information for this application, including its client_id and
# client_secret. You can acquire an OAuth 2.0 client ID and client secret from
# the Google Developers Console at
#   https://console.developers.google.com/project/_/apiui/credential
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
with information from the Developers Console
https://console.developers.google.com

For more information about the client_secrets.json file format, please visit:
https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
""" % os.path.abspath(os.path.join(os.path.dirname(__file__),
                                   CLIENT_SECRETS_FILE))

# Authorize the request and store authorization credentials.
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


# Call the API's watermarks.set method to upload the watermark image and
# associate it with the proper channel.
def set_watermark(youtube, channel_id, file, metadata):
  try:
    youtube.watermarks().set(
      channelId=channel_id,
      media_body=file,
      body=metadata,
    ).execute()
  except HttpError as e:
    print "Error while setting watermark: %s" % e.content
    raise e


if __name__ == "__main__":
  # The "channelid" option specifies the YouTube channel ID that uniquely
  # identifies the channel for which the watermark image is being updated.
  argparser.add_argument("--channelid", dest="channelid",
    help="Required; ID for channel that is having its watermark updated.")
  # The "file" option specifies the path to the image being uploaded.
  argparser.add_argument("--file", dest="file",
    help="Required; path to watermark image file.")
  # The "metadata" option specifies the JSON for the watermark resource
  # provided with the request.
  argparser.add_argument("--metadata", dest="metadata",
    help="Required; watermark metadata in JSON format.")
  args = argparser.parse_args()

  if not args.channelid:
    argparser.print_help()
    exit()

  youtube = get_authenticated_service(args)

  if not args.file or not os.path.exists(args.file):
    exit("Please specify a valid file using the --file= parameter.")
  if not args.metadata:
    exit("Please specify watermark metadata using the --metadata= parameter.")
  set_watermark(youtube, args.channelid, args.file,
                json.loads(args.metadata))
  print "The watermark was successfully set."
