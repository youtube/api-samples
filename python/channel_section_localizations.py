#!/usr/bin/python

# Retrieve or set localized channel section metadata.
# See the Prerequisites section in the README file in this directory
# for more information about running this sample locally.

# Usage examples:
# python channel_sections_localizations.py --action=list --mine=True
# python channel_sections_localizations.py --action='<action>' --channel_section_id='<channel_section_id>' --default_language='<default_language>' --language='<language>' --title='<title>'

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

ACTIONS = ('get', 'list', 'set')

# Authorize the request and store authorization credentials.
def get_authenticated_service():
  flow = InstalledAppFlow.from_client_secrets_file(CLIENT_SECRETS_FILE, SCOPES)
  credentials = flow.run_console()
  return build(API_SERVICE_NAME, API_VERSION, credentials = credentials)

# Call the API's channelSections.update method to update an existing channel section's
# default language, and localized title in a specific language.
def set_channel_section_localization(youtube, args):

  # Retrieve the snippet and localizations for the channel section.
  results = youtube.channelSections().list(
    part='snippet,contentDetails,localizations',
    id=args.channel_section_id
  ).execute()

  channel_section = results['items'][0]

  # If the language argument is set, set the localized title for that language.
  # The "title" argument has a default value to make the script simpler to run
  # as a demo. But in an actual app, you would likely want to set its value.
  if args.language and args.language != '':
    if 'localizations' not in channel_section:
      channel_section['localizations'] = {}

    channel_section['localizations'][args.language] = {
      'title': args.title,
    }

  # If the default language is set AND there is localized metadata for that
  # language, set the channel section's title and description to match the
  # localized title and description for the newly set default language.
  if args.default_language:
    channel_section['snippet']['defaultLanguage'] = args.default_language
    if args.default_language in channel_section['localizations']:
      channel_section['snippet']['title'] = (
          channel_section['localizations'][args.default_language]['title'])

  update_result = youtube.channelSections().update(
    part='snippet,contentDetails,localizations',
    body=channel_section
  ).execute()

  if args.language:
    for language in update_result['localizations']:
      # Languages with locales, like "pt-br" are returned as pt-BR in metadata.
      # This ensures that the language specified when running the script can be
      # matched to the language returned in the metadata.
      if language.lower() == args.language.lower():
        localization = update_result['localizations'][args.language]
        print ('Updated channel section %s localized title to %s in \'%s\'.' %
               (args.channel_section_id, localization['title'], args.language))
        break

  if (args.default_language and
      args.default_language == update_result['snippet']['defaultLanguage']):
    print 'Updated default language to %s.' % args.default_language

# Call the API's channelSections.list method to retrieve an existing channel section localization.
# If the localized text is not available in the requested language,
# this method will return text in the default language.
def get_channel_section_localization(youtube, args):
  results = youtube.channelSections().list(
    part='snippet',
    id=args.channel_section_id,
    hl=args.language
  ).execute()

  # The localized object contains localized text if the hl parameter specified
  # a language for which localized text is available. Otherwise, the localized
  # object will contain metadata in the default language.
  localized = results['items'][0]['snippet']['localized']

  print('The channel section\'s title is \'%s\' in language \'%s\'.' %
        (localized['title'], language))

# Call the API's channelSections.list method to list all existing localizations
# for the channel section.
def list_channel_section_localizations(youtube, args):
  results = youtube.channelSections().list(
    part='snippet,localizations',
    id=args.channel_section_id
  ).execute()

  localizations = results['items'][0]['localizations']

  for language, localization in localizations.iteritems():
    print('The channel section title is \'%s\' in language \'%s\'.' %
          (localization['title'], language))

# Call the API's channelSections.list method to list localizations for all
# channel sections in the authorizing user\'s channel. This function might
# be called as a way of identifying the ID of the section you actually want
# to update.
def list_my_channel_section_localizations(youtube, args):
  results = youtube.channelSections().list(
    part='snippet,localizations',
    mine=True,
  ).execute()

  print(results)

  for i in range(0, len(results['items'])):
    item = results['items'][i]
    print str(item['snippet']['position']) + ':'
    print '    ID: ' + item['id']
    print '    Type: ' + item['snippet']['type']
    if ('title' in item['snippet'] and item['snippet']['title']):
      print '    Title: ' + str(item['snippet']['title'])

    if 'localizations' in results['items'][i]:
      localizations = results['items'][i]['localizations']
      print('    Localized titles by language:')
      for language, localization in localizations.iteritems():
        print('       ' + language + ': ' + localization['title'])
    else:
      print('    No localizations. :(\n')

  #for language, localization in localizations.iteritems():
  #  print('The channel section title is \'%s\' in language \'%s\'.' %
  #        (localization['title'], language))


if __name__ == '__main__':
  parser = argparse.ArgumentParser()
  # The 'action' option specifies the action to be processed.
  parser.add_argument('--action', choices=ACTIONS, required=True,
      help='The type of operation. Supported values are: "get", "list", "set"')
  # The ID of the channel section for which data is being retrieved or updated.
  parser.add_argument('--channel_section_id',
      help='ID of channel section for which localized data is being ' +
           'retrieved or updated.')
  # The language of the channel section's default metadata.
  parser.add_argument('--default_language',
      help='Default language of the channel section to update.')
  # The language of the localization that is being processed.
  parser.add_argument('--language', help='Language of the localized metadata.')
  # The localized channel section title for the specified language.
  parser.add_argument('--title',
      help='Localized title of the channel section to be set.',
      default='Localized Title')
  # The language of the channel section's default metadata.
  parser.add_argument('--mine', type=bool, default=False,
      help='List localizations for all of my channel sections.')

  args = parser.parse_args()

  if not args.channel_section_id and not args.mine:
    exit('You must either specify a channel section ID using the ' +
         '--channel_section_id argument or retrieve localizations ' +
         'for all of your channel sections by setting the --mine ' +
         'argument to True.')

  youtube = get_authenticated_service()
  try:
    if args.action == 'set':
      set_channel_section_localization(youtube, args)
    elif args.action == 'get':
      get_channel_section_localization(youtube, args)
    elif args.action == 'list':
      if args.mine:
        list_my_channel_section_localizations(youtube, args)
      else:
        list_channel_section_localizations(youtube, args)
    else:
      exit('Please specify a valid action using the --action= parameter.')
  except HttpError, e:
    print 'An HTTP error %d occurred:\n%s' % (e.resp.status, e.content)
