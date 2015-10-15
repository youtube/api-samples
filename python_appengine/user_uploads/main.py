import os
import urllib
import webapp2
import jinja2

from apiclient.discovery import build
from optparse import OptionParser

import json

JINJA_ENVIRONMENT = jinja2.Environment(
    loader=jinja2.FileSystemLoader(os.path.dirname(__file__)),
    extensions=['jinja2.ext.autoescape'])

REGISTRATION_INSTRUCTIONS = """
    You must set up a project and get an API key to run this code. Please see
    the instructions for creating a project and a key at <a
    href="https://developers.google.com/youtube/registering_an_application"
    >https://developers.google.com/youtube/registering_an_application</a>.
    <br><br>
    Make sure that you have enabled the YouTube Data API (v3) and the Freebase
    API for your project."""

# Set API_KEY to the "API key" value from the Google Developers Console:
# https://console.developers.google.com/project/_/apiui/credential
# Please ensure that you have enabled the YouTube Data API and Freebase API
# for your project.
API_KEY = "REPLACE_ME"
YOUTUBE_API_SERVICE_NAME = "youtube"
YOUTUBE_API_VERSION = "v3"
FREEBASE_SEARCH_URL = "https://www.googleapis.com/freebase/v1/search?%s"
QUERY_TERM = "dog"

class MainHandler(webapp2.RequestHandler):

  def get(self):
    if API_KEY == 'REPLACE_ME':
      self.response.write(REGISTRATION_INSTRUCTIONS)
    else:
      # Present a list of Freebase topic IDs for the query term
      self.request_channel()

  def request_channel(self):
    # Display a text box where the user can enter a channel name or
    # channel ID.
    select_channel_page = '''
        <html>
          <body>
            <p>Which channel's videos do you want to see?</p>
            <form method="post">
              <p>
                <select name="channel_type">
                  <option value="id">Channel ID</option>
                  <option value="name">Channel name</option>
                </select>&nbsp;&nbsp;
                <input name="channel" size="30">
              </p>
              <p><input type="submit" /></p>
            </form>
          </body>
        </html>
    '''

    # Display the HTML page that shows the form.
    self.response.out.write(select_channel_page)

  def post(self):
    # Service for calling the YouTube API
    youtube = build(YOUTUBE_API_SERVICE_NAME,
                    YOUTUBE_API_VERSION,
                    developerKey=API_KEY)

    # Use form inputs to create request params for channel details
    channel_type = self.request.get('channel_type')
    channels_response = None
    if channel_type == 'id':
      channels_response = youtube.channels().list(
          id=self.request.get('channel'),
          part='snippet,contentDetails'
      ).execute()
    else:
      channels_response = youtube.channels().list(
          forUsername=self.request.get('channel'),
          part='snippet,contentDetails'
      ).execute()

    channel_name = ''
    videos = []

    for channel in channels_response['items']:
      uploads_list_id = channel['contentDetails']['relatedPlaylists']['uploads']
      channel_name = channel['snippet']['title']
      
      next_page_token = ''
      while next_page_token is not None:
        playlistitems_response = youtube.playlistItems().list(
            playlistId=uploads_list_id,
            part='snippet',
            maxResults=50,
            pageToken=next_page_token
        ).execute()

        for playlist_item in playlistitems_response['items']:
          videos.append(playlist_item)
          
        next_page_token = playlistitems_response.get('tokenPagination', {}).get(
            'nextPageToken')
        
        if len(videos) > 100:
          break

    template_values = {
      'channel_name': channel_name,
      'videos': videos
    }

    self.response.headers['Content-type'] = 'text/html'
    template = JINJA_ENVIRONMENT.get_template('index.html')
    self.response.write(template.render(template_values))

app = webapp2.WSGIApplication([
  ('/.*', MainHandler),
], debug=True)
