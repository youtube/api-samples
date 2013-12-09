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

# Set API_KEY to the "API key" value from the "Access" tab of the
# Google APIs Console http://code.google.com/apis/console#access
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
      self.list_topics(QUERY_TERM)

  def list_topics(self, QUERY_TERM):
    # Retrieve a list of Freebase topics associated with the query term
    freebase_params = dict(query=QUERY_TERM, key=API_KEY)
    freebase_url = FREEBASE_SEARCH_URL % urllib.urlencode(freebase_params)
    freebase_response = json.loads(urllib.urlopen(freebase_url).read())

    if len(freebase_response["result"]) == 0:
      exit("No matching terms were found in Freebase.")

    # Create a page that shows a select box listing the topics.
    # When the user selects a topic and submits the form, the
    # 'post' method below will handle the form submission and
    # retrieve videos for the selected topic.
    select_topic_page = ('''
        <html>
          <body>
            <p>The following topics were found:</p>
            <form method="post">
              <select name="topic">
    ''')
    for result in freebase_response["result"]:
      select_topic_page += ('<option value="' + result["mid"] + '">' +
                            result.get("name", "Unknown") + '</option>')

    select_topic_page += '''
              </select>
              <p><input type="submit" /></p>
            </form>
          </body>
        </html>
    '''

    # Display the HTML page listing the topic choices.
    self.response.out.write(select_topic_page)

  def post(self):
    topic_id = self.request.get('topic')

    # Service for calling the YouTube API
    youtube = build(YOUTUBE_API_SERVICE_NAME,
                    YOUTUBE_API_VERSION,
                    developerKey=API_KEY)

    # Execute the search request using default query term and retrieved topic.
    search_response = youtube.search().list(
      part = 'id,snippet',
      type = 'video',
      topicId = topic_id
    ).execute()

    videos = []

    for search_result in search_response.get("items", []):
      videos.append(search_result)

    template_values = {
      'videos': videos
    }

    self.response.headers['Content-type'] = 'text/html'
    template = JINJA_ENVIRONMENT.get_template('index.html')
    self.response.write(template.render(template_values))

app = webapp2.WSGIApplication([
  ('/.*', MainHandler),
], debug=True)
