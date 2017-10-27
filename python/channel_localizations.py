#!/usr/bin/python

# Usage example:
# python channel_localizations.py --action='<action>' --channel_id='<channel_id>' --default_language='<default_language>' --language='<language>' --title='<title>' --description='<description>'

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

# Authorize the request and store authorization credentials.
def get_authenticated_service():
  flow = InstalledAppFlow.from_client_secrets_file(CLIENT_SECRETS_FILE, SCOPES)
  credentials = flow.run_console()
  return build(API_SERVICE_NAME, API_VERSION, credentials = credentials)

# Call the API's channels.update method to update an existing channel's default language,
# and localized description in a specific language.
def set_channel_localization(youtube, args):
  results = youtube.channels().list(
    part='localizations',
    id=args.channel_id
  ).execute()

  channel = results['items'][0]

  # Ensure that a value is set for the resource's snippet.defaultLanguage property.
  # To set the snippet.defaultLanguage property for a channel resource,
  # you actually need to update the brandingSettings.channel.defaultLanguage property.
  if 'localizations' not in channel:
    channel['localizations'] = {}
  if args.title and args.description and args.language:
    channel['localizations'][args.language] = {
      'title': args.title,
      'description': args.description
  }

  # Set the default language if it is provided as an argument
  if args.default_language:
    results = youtube.channels().list(
      part='brandingSettings',
      id=args.channel_id
    ).execute()
    branding_settings_channel = results['items'][0]
    # This property must be removed when changing the default language
    # or set to the original channel title to avoid a
    # channelTitleUpdateForbidden error.
    del branding_settings_channel['brandingSettings']['channel']['title']
    branding_settings_channel['brandingSettings']['channel']['defaultLanguage'] = (
        args.default_language)
    language_result = youtube.channels().update(
      part='brandingSettings',
      body=branding_settings_channel
    ).execute()
    updated_default_language = (
        language_result['brandingSettings']['channel']['defaultLanguage'])
    print 'Updated language to %s' % updated_default_language

  update_result = youtube.channels().update(
    part='localizations',
    body=channel
  ).execute()

  localization = update_result['localizations'][args.language]

  print ('Updated channel \'%s\' localized title and description to '
         '\'%s\' and \'%s\' in language \'%s\'' %
         (args.channel_id, localization['title'], localization['description'], args.language))


# Call the API's channels.list method to retrieve an existing channel localization.
# If the localized text is not available in the requested language,
# this method will return text in the default language.
def get_channel_localization(youtube, channel_id, language):
  results = youtube.channels().list(
    part='snippet',
    id=channel_id,
    hl=language
  ).execute()

  # The localized object contains localized text if the hl parameter specified
  # a language for which localized text is available. Otherwise, the localized
  # object will contain metadata in the default language.
  localized = results['items'][0]['snippet']['localized']

  print 'Channel description is \'%s\' in language \'%s\'' % (localized['description'], language)


# Call the API's channels.list method to list the existing channel localizations.
def list_channel_localizations(youtube, channel_id):
  results = youtube.channels().list(
    part='snippet,localizations',
    id=channel_id
  ).execute()

  if 'localizations' in results['items'][0]:
    localizations = results['items'][0]['localizations']
    for language, localization in localizations.iteritems():
      print 'Channel description is \'%s\' in language \'%s\'' % (localization['description'], language)
  else:
    print 'There aren\'t any localizations for this channel yet.'

if __name__ == '__main__':
  parser = argparse.ArgumentParser()
  # The 'action' option specifies the action to be processed.
  parser.add_argument('--action', help='Action')
  # The 'channel_id' option specifies the ID of the selected YouTube channel.
  parser.add_argument('--channel_id',
    help='ID for channel for which the localization will be applied.')
  # The 'default_language' option specifies the language of the channel's default metadata.
  parser.add_argument('--default_language', help='Default language of the channel to update.',
    default=None)
  # The 'language' option specifies the language of the localization that is being processed.
  parser.add_argument('--language', help='Language of the localization.', default='de')
  # The 'title' option specifies the localized title of the chanel to be set.
  parser.add_argument('--title', help='Localized title of the channel to be set.',
    default='Localized title')
  # The 'description' option specifies the localized description of the chanel to be set.
  parser.add_argument('--description', help='Localized description of the channel to be set.',
    default='Localized description')

  args = parser.parse_args()

  if not args.channel_id:
    exit('Please specify channel id using the --channel_id= parameter.')

  youtube = get_authenticated_service()
  try:
    if args.action == 'set':
      set_channel_localization(youtube, args)
    elif args.action == 'get':
      get_channel_localization(youtube, args.channel_id, args.language)
    elif args.action == 'list':
      list_channel_localizations(youtube, args.channel_id)
    else:
      exit('Please specify a valid action using the --action= parameter.')
  except HttpError, e:
    print 'An HTTP error %d occurred:\n%s' % (e.resp.status, e.content)
  else:
    print 'Set or retrieved localized metadata for a channel.'                                                         
