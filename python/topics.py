#!/usr/bin/python

from apiclient.discovery import build
from apiclient.errors import HttpError
from oauth2client.tools import argparser

import json
import urllib


# Set DEVELOPER_KEY to the API key value from the APIs & auth > Registered apps
# tab of
#   https://cloud.google.com/console
# Please ensure that you have enabled the YouTube Data API for your project.
DEVELOPER_KEY = "REPLACE_ME"
YOUTUBE_API_SERVICE_NAME = "youtube"
YOUTUBE_API_VERSION = "v3"
FREEBASE_SEARCH_URL = "https://www.googleapis.com/freebase/v1/search?%s"

def get_topic_id(options):
  # Retrieve a list of Freebase topics associated with the provided query term.
  freebase_params = dict(query=options.query, key=DEVELOPER_KEY)
  freebase_url = FREEBASE_SEARCH_URL % urllib.urlencode(freebase_params)
  freebase_response = json.loads(urllib.urlopen(freebase_url).read())

  if len(freebase_response["result"]) == 0:
    exit("No matching terms were found in Freebase.")

  # Display the list of matching Freebase topics.
  mids = []
  index = 1
  print "The following topics were found:"
  for result in freebase_response["result"]:
    mids.append(result["mid"])
    print "  %2d. %s (%s)" % (index, result.get("name", "Unknown"),
      result.get("notable", {}).get("name", "Unknown"))
    index += 1

  # Display a prompt for the user to select a topic and return the topic ID
  # of the selected topic.
  mid = None
  while mid is None:
    index = raw_input("Enter a topic number to find related YouTube %ss: " %
      options.type)
    try:
      mid = mids[int(index) - 1]
    except ValueError:
      pass
  return mid


def youtube_search(mid, options):
  youtube = build(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION,
  developerKey=DEVELOPER_KEY)

  # Call the search.list method to retrieve results associated with the
  # specified Freebase topic.
  search_response = youtube.search().list(
    topicId=mid,
    type=options.type,
    part="id,snippet",
    maxResults=options.max_results
  ).execute()

  # Print the title and ID of each matching resource.
  for search_result in search_response.get("items", []):
    if search_result["id"]["kind"] == "youtube#video":
      print "%s (%s)" % (search_result["snippet"]["title"],
        search_result["id"]["videoId"])
    elif search_result["id"]["kind"] == "youtube#channel":
      print "%s (%s)" % (search_result["snippet"]["title"],
        search_result["id"]["channelId"])
    elif search_result["id"]["kind"] == "youtube#playlist":
      print "%s (%s)" % (search_result["snippet"]["title"],
        search_result["id"]["playlistId"])


if __name__ == "__main__":
  argparser.add_argument("--query", help="Freebase search term", default="Google")
  argparser.add_argument("--max-results", help="Max YouTube results",
    default=25)
  argparser.add_argument("--type",
    help="YouTube result type: video, playlist, or channel", default="channel")
  args = argparser.parse_args()

  mid = get_topic_id(args)
  try:
    youtube_search(mid, args)
  except HttpError, e:
    print "An HTTP error %d occurred:\n%s" % (e.resp.status, e.content)
