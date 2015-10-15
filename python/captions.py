#!/usr/bin/python

# Usage example:
# python captions.py --videoid='<video_id>' --name='<name>' --file='<file>' --language='<language>' --action='action'

import httplib2
import os
import sys

from apiclient.discovery import build_from_document
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
# authenticated user's account and requires requests to use an SSL connection.
YOUTUBE_READ_WRITE_SSL_SCOPE = "https://www.googleapis.com/auth/youtube.force-ssl"
YOUTUBE_API_SERVICE_NAME = "youtube"
YOUTUBE_API_VERSION = "v3"

# This variable defines a message to display if the CLIENT_SECRETS_FILE is
# missing.
MISSING_CLIENT_SECRETS_MESSAGE = """
WARNING: Please configure OAuth 2.0

To make this sample run you will need to populate the client_secrets.json file
found at:
   %s
with information from the APIs Console
https://console.developers.google.com

For more information about the client_secrets.json file format, please visit:
https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
""" % os.path.abspath(os.path.join(os.path.dirname(__file__),
                                   CLIENT_SECRETS_FILE))

# Authorize the request and store authorization credentials.
def get_authenticated_service(args):
  flow = flow_from_clientsecrets(CLIENT_SECRETS_FILE, scope=YOUTUBE_READ_WRITE_SSL_SCOPE,
    message=MISSING_CLIENT_SECRETS_MESSAGE)

  storage = Storage("%s-oauth2.json" % sys.argv[0])
  credentials = storage.get()

  if credentials is None or credentials.invalid:
    credentials = run_flow(flow, storage, args)

  # Trusted testers can download this discovery document from the developers page
  # and it should be in the same directory with the code.
  with open("youtube-v3-api-captions.json", "r") as f:
    doc = f.read()
    return build_from_document(doc, http=credentials.authorize(httplib2.Http()))


# Call the API's captions.list method to list the existing caption tracks.
def list_captions(youtube, video_id):
  results = youtube.captions().list(
    part="snippet",
    videoId=video_id
  ).execute()

  for item in results["items"]:
    id = item["id"]
    name = item["snippet"]["name"]
    language = item["snippet"]["language"]
    print "Caption track '%s(%s)' in '%s' language." % (name, id, language)

  return results["items"]


# Call the API's captions.insert method to upload a caption track in draft status.
def upload_caption(youtube, video_id, language, name, file):
  insert_result = youtube.captions().insert(
    part="snippet",
    body=dict(
      snippet=dict(
        videoId=video_id,
        language=language,
        name=name,
        isDraft=True
      )
    ),
    media_body=file
  ).execute()

  id = insert_result["id"]
  name = insert_result["snippet"]["name"]
  language = insert_result["snippet"]["language"]
  status = insert_result["snippet"]["status"]
  print "Uploaded caption track '%s(%s) in '%s' language, '%s' status." % (name,
      id, language, status)


# Call the API's captions.update method to update an existing caption track's draft status
# and publish it. If a new binary file is present, update the track with the file as well.
def update_caption(youtube, caption_id, file):
  update_result = youtube.captions().update(
    part="snippet",
    body=dict(
      id=caption_id,
      snippet=dict(
        isDraft=False
      )
    ),
    media_body=file
  ).execute()

  name = update_result["snippet"]["name"]
  isDraft = update_result["snippet"]["isDraft"]
  print "Updated caption track '%s' draft status to be: '%s'" % (name, isDraft)
  if file:
    print "and updated the track with the new uploaded file."


# Call the API's captions.download method to download an existing caption track.
def download_caption(youtube, caption_id, tfmt):
  subtitle = youtube.captions().download(
    id=caption_id,
    tfmt=tfmt
  ).execute()

  print "First line of caption track: %s" % (subtitle)

# Call the API's captions.delete method to delete an existing caption track.
def delete_caption(youtube, caption_id):
  youtube.captions().delete(
    id=caption_id
  ).execute()

  print "caption track '%s' deleted succesfully" % (caption_id)


if __name__ == "__main__":
  # The "videoid" option specifies the YouTube video ID that uniquely
  # identifies the video for which the caption track will be uploaded.
  argparser.add_argument("--videoid",
    help="Required; ID for video for which the caption track will be uploaded.")
  # The "name" option specifies the name of the caption trackto be used.
  argparser.add_argument("--name", help="Caption track name", default="YouTube for Developers")
  # The "file" option specifies the binary file to be uploaded as a caption track.
  argparser.add_argument("--file", help="Captions track file to upload")
  # The "language" option specifies the language of the caption track to be uploaded.
  argparser.add_argument("--language", help="Caption track language", default="en")
  # The "captionid" option specifies the ID of the caption track to be processed.
  argparser.add_argument("--captionid", help="Required; ID of the caption track to be processed")
  # The "action" option specifies the action to be processed.
  argparser.add_argument("--action", help="Action", default="all")


  args = argparser.parse_args()

  if (args.action in ('upload', 'list', 'all')):
    if not args.videoid:
          exit("Please specify videoid using the --videoid= parameter.")

  if (args.action in ('update', 'download', 'delete')):
    if not args.captionid:
          exit("Please specify captionid using the --captionid= parameter.")

  if (args.action in ('upload', 'all')):
    if not args.file:
      exit("Please specify a caption track file using the --file= parameter.")
    if not os.path.exists(args.file):
      exit("Please specify a valid file using the --file= parameter.")

  youtube = get_authenticated_service(args)
  try:
    if args.action == 'upload':
      upload_caption(youtube, args.videoid, args.language, args.name, args.file)
    elif args.action == 'list':
      list_captions(youtube, args.videoid)
    elif args.action == 'update':
      update_caption(youtube, args.captionid, args.file);
    elif args.action == 'download':
      download_caption(youtube, args.captionid, 'srt')
    elif args.action == 'delete':
      delete_caption(youtube, args.captionid);
    else:
      # All the available methods are used in sequence just for the sake of an example.
      upload_caption(youtube, args.videoid, args.language, args.name, args.file)
      captions = list_captions(youtube, args.videoid)

      if captions:
        first_caption_id = captions[0]['id'];
        update_caption(youtube, first_caption_id, None);
        download_caption(youtube, first_caption_id, 'srt')
        delete_caption(youtube, first_caption_id);
  except HttpError, e:
    print "An HTTP error %d occurred:\n%s" % (e.resp.status, e.content)
  else:
    print "Created and managed caption tracks."
