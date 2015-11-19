#!/usr/bin/python

# Usage example:
# python video_localizations.py --action='<action>' --video_id='<video_id>' --default_language='<default_language>' --language='<language>' --title='<title>' --description='<description>'

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
with information from the APIs Console
https://console.developers.google.com

For more information about the client_secrets.json file format, please visit:
https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
""" % os.path.abspath(os.path.join(os.path.dirname(__file__),
                                   CLIENT_SECRETS_FILE))

# Authorize the request and store authorization credentials.
def get_authenticated_service(args):
  flow = flow_from_clientsecrets(CLIENT_SECRETS_FILE, scope=YOUTUBE_READ_WRITE_SCOPE,
    message=MISSING_CLIENT_SECRETS_MESSAGE)

  storage = Storage("%s-oauth2.json" % sys.argv[0])
  credentials = storage.get()

  if credentials is None or credentials.invalid:
    credentials = run_flow(flow, storage, args)

  return build(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION,
    http=credentials.authorize(httplib2.Http()))


# Call the API's videos.update method to update an existing video's default language,
# localized title and description in a specific language.
def set_video_localization(youtube, video_id, default_language, language, title, description):
  results = youtube.videos().list(
    part="snippet,localizations",
    id=video_id
  ).execute()

  video = results["items"][0]
  # Ensure that a value is set for the resource's snippet.defaultLanguage property.
  video["snippet"]["defaultLanguage"] = default_language
  if "localizations" not in video:
    video["localizations"] = {}
  video["localizations"][language] = {
    "title": title,
    "description": description
  }

  update_result = youtube.videos().update(
    part="snippet,localizations",
    body=video
  ).execute()

  localization = update_result["localizations"][language]

  print ("Updated video '%s' default language to '%s', localized title to '%s'"
         " and description to '%s' in language '%s'"
          % (video_id, default_language, localization["title"], localization["description"], language))


# Call the API's videos.list method to retrieve an existing video localization.
# If the localized text is not available in the requested language,
# this method will return text in the default language.
def get_video_localization(youtube, video_id, language):
  results = youtube.videos().list(
    part="snippet",
    id=video_id,
    hl=language
  ).execute()

  # The localized object contains localized text if the hl parameter specified
  # a language for which localized text is available. Otherwise, the localized
  # object will contain metadata in the default language.
  localized = results["items"][0]["snippet"]["localized"]

  print ("Video title is '%s' and description is '%s' in language '%s'"
         % (localized["title"], localized["description"], language))


# Call the API's videos.list method to list the existing video localizations.
def list_video_localizations(youtube, video_id):
  results = youtube.videos().list(
    part="snippet,localizations",
    id=video_id
  ).execute()

  localizations = results["items"][0]["localizations"]

  for language, localization in localizations.iteritems():
    print ("Video title is '%s' and description is '%s' in language '%s'"
           % (localization["title"], localization["description"], language))


if __name__ == "__main__":
  # The "action" option specifies the action to be processed.
  argparser.add_argument("--action", help="Action")
  # The "video_id" option specifies the ID of the selected YouTube video.
  argparser.add_argument("--video_id",
    help="ID for video for which the localization will be applied.")
  # The "default_language" option specifies the language of the video's default metadata.
  argparser.add_argument("--default_language", help="Default language of the video to update.",
    default="en")
  # The "language" option specifies the language of the localization that is being processed.
  argparser.add_argument("--language", help="Language of the localization.", default="de")
  # The "title" option specifies the localized title of the video to be set.
  argparser.add_argument("--title", help="Localized title of the video to be set.",
    default="Localized Title")
  # The "description" option specifies the localized description of the video to be set.
  argparser.add_argument("--description", help="Localized description of the video to be set.",
    default="Localized Description")

  args = argparser.parse_args()

  if not args.video_id:
    exit("Please specify video id using the --video_id= parameter.")

  youtube = get_authenticated_service(args)
  try:
    if args.action == 'set':
      set_video_localization(youtube, args.video_id, args.default_language, args.language, args.title, args.description)
    elif args.action == 'get':
      get_video_localization(youtube, args.video_id, args.language)
    elif args.action == 'list':
      list_video_localizations(youtube, args.video_id)
    else:
      exit("Please specify a valid action using the --action= parameter.")
  except HttpError, e:
    print "An HTTP error %d occurred:\n%s" % (e.resp.status, e.content)
  else:
    print "Set and retrieved localized metadata for a video."
