#!/usr/bin/python

# Usage example:
# python playlist_localizations.py --action='<action>' --playlist_id='<playlist_id>' --default_language='<default_language>' --language='<language>' --title='<title>' --description='<description>'

import argparse
import os

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

ACTIONS = ('get', 'list', 'set')

# Authorize the request and store authorization credentials.
def get_authenticated_service():
  flow = InstalledAppFlow.from_client_secrets_file(CLIENT_SECRETS_FILE, SCOPES)
  credentials = flow.run_console()
  return build(API_SERVICE_NAME, API_VERSION, credentials = credentials)

# Call the API's playlists.update method to update an existing playlist's default language,
# localized title and description in a specific language.
def set_playlist_localization(youtube, args):

  results = youtube.playlists().list(
    part='snippet,localizations',
    id=args.playlist_id
  ).execute()

  playlist = results['items'][0]

  # If the language argument is set, set the localized title and description
  # for that language. The "title" and "description" arguments have default
  # values to make the script simpler to run as a demo. In an actual app, you
  # would likely want to set those arguments also.
  if args.language and args.language != '':
    if 'localizations' not in playlist:
      playlist['localizations'] = {}

    playlist['localizations'][args.language] = {
      'title': args.title,
      'description': args.description
    }

  # If the default language is set AND there is localized metadata for that
  # language, set the video's title and description to match the localized
  # title and description for the newly set default language.
  if args.default_language:
    playlist['snippet']['defaultLanguage'] = args.default_language
    if args.default_language in playlist['localizations']:
      playlist['snippet']['title'] = (
          playlist['localizations'][args.default_language]['title'])
      playlist['snippet']['description'] = (
          playlist['localizations'][args.default_language]['description'])

  print(playlist)

  # Update the playlist resource
  update_result = youtube.playlists().update(
    part='snippet,localizations',
    body=playlist
  ).execute()

  # Print the actions taken by running the script.
  if args.language:
    for language in update_result['localizations']:
      # Languages with locales, like "pt-br" are returned as pt-BR in metadata.
      # This ensures that the language specified when running the script can be
      # matched to the language returned in the metadata.
      if language.lower() == args.language.lower():
        localization = update_result['localizations'][args.language]
        print ('Updated playlist \'%s\' localized title to \'%s\''
               ' and description to \'%s\' in language \'%s\'' %
               (args.playlist_id,
                localization['title'],
                localization['description'],
                args.language))
        break

  if (args.default_language and
      args.default_language == update_result['snippet']['defaultLanguage']):
    print 'Updated default language to %s' % args.default_language

# Call the API's playlists.list method to retrieve an existing playlist localization.
# If the localized text is not available in the requested language,
# this method will return text in the default language.
def get_playlist_localization(youtube, args):
  results = youtube.playlists().list(
    part='snippet',
    id=args.playlist_id,
    hl=args.language
  ).execute()

  # The localized object contains localized text if the hl parameter specified
  # a language for which localized text is available. Otherwise, the localized
  # object will contain metadata in the default language.
  localized = results['items'][0]['snippet']['localized']

  print ('Playlist title is "%s" and description is "%s" in language "%s"'
         % (localized['title'], localized['description'], args.language))


# Call the API's playlists.list method to list existing localizations
# for the playlist.
def list_playlist_localizations(youtube, args):
  results = youtube.playlists().list(
    part='snippet,localizations',
    id=args.playlist_id
  ).execute()

  if 'localizations' in results['items'][0]:
    localizations = results['items'][0]['localizations']

    for language, localization in localizations.iteritems():
      print ('Playlist title is "%s" and description is "%s" in language "%s"'
             % (localization['title'], localization['description'], language))
  else:
    print 'There aren\'t any localizations for this playlist yet.'


if __name__ == '__main__':
  parser = argparse.ArgumentParser()
  # The action to be processed: 'get', 'list', and 'set' are supported.
  parser.add_argument('--action', required=True, help='Action', choices=ACTIONS)
  # The ID of the selected YouTube olaylist.
  parser.add_argument('--playlist_id',
    help='The playlist ID for which localizations are being set or retrieved.',
    required=True)
  # The langauge of the playlist's default metadata.
  parser.add_argument('--default_language',
    help='Default language to set for the playlist.')
  # The language of the localization that is being set or retrieved.
  parser.add_argument('--language', help='Language of the localization.')
  # The localized title to set in the specified language.
  parser.add_argument('--title',
    help='Localized title to be set for the playlist.',
    default='Localized Title')
  # The localized description to set in the specified language.
  parser.add_argument('--description',
    help='Localized description to be set for the playlist.',
    default='Localized Description')

  args = parser.parse_args()

  youtube = get_authenticated_service()

  try:
    if args.action == 'set':
      set_playlist_localization(youtube, args)
    elif args.action == 'get':
      get_playlist_localization(youtube, args)
    elif args.action == 'list':
      list_playlist_localizations(youtube, args)
  except HttpError, e:
    print 'An HTTP error %d occurred:\n%s' % (e.resp.status, e.content)
