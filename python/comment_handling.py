#!/usr/bin/python

# Usage example:
# python comments.py --videoid='<video_id>' --text='<text>'

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
  with open("youtube-v3-discoverydocument.json", "r") as f:
    doc = f.read()
    return build_from_document(doc, http=credentials.authorize(httplib2.Http()))


# Call the API's commentThreads.list method to list the existing comment threads.
def get_comment_threads(youtube, video_id):
  results = youtube.commentThreads().list(
    part="snippet",
    videoId=video_id,
    textFormat="plainText"
  ).execute()

  for item in results["items"]:
    comment = item["snippet"]["topLevelComment"]
    author = comment["snippet"]["authorDisplayName"]
    text = comment["snippet"]["textDisplay"]
    print "Comment by %s: %s" % (author, text)

  return results["items"]


# Call the API's comments.list method to list the existing comment replies.
def get_comments(youtube, parent_id):
  results = youtube.comments().list(
    part="snippet",
    parentId=parent_id,
    textFormat="plainText"
  ).execute()

  for item in results["items"]:
    author = item["snippet"]["authorDisplayName"]
    text = item["snippet"]["textDisplay"]
    print "Comment by %s: %s" % (author, text)

  return results["items"]


# Call the API's comments.insert method to reply to a comment.
# (If the intention is to create a new to-level comment, commentThreads.insert
# method should be used instead.)
def insert_comment(youtube, parent_id, text):
  insert_result = youtube.comments().insert(
    part="snippet",
    body=dict(
      snippet=dict(
        parentId=parent_id,
        textOriginal=text
      )
    )
  ).execute()

  author = insert_result["snippet"]["authorDisplayName"]
  text = insert_result["snippet"]["textDisplay"]
  print "Replied to a comment for %s: %s" % (author, text)


# Call the API's comments.update method to update an existing comment.
def update_comment(youtube, comment):
  comment["snippet"]["textOriginal"] = 'updated'
  update_result = youtube.comments().update(
    part="snippet",
    body=comment
  ).execute()

  author = update_result["snippet"]["authorDisplayName"]
  text = update_result["snippet"]["textDisplay"]
  print "Updated comment for %s: %s" % (author, text)


# Call the API's comments.setModerationStatus method to set moderation status of an
# existing comment.
def set_moderation_status(youtube, comment):
  youtube.comments().setModerationStatus(
    id=comment["id"],
    moderationStatus="published"
  ).execute()

  print "%s moderated succesfully" % (comment["id"])


# Call the API's comments.markAsSpam method to mark an existing comment as spam.
def mark_as_spam(youtube, comment):
  youtube.comments().markAsSpam(
    id=comment["id"]
  ).execute()

  print "%s marked as spam succesfully" % (comment["id"])


# Call the API's comments.delete method to delete an existing comment.
def delete_comment(youtube, comment):
  youtube.comments().delete(
    id=comment["id"]
  ).execute()

  print "%s deleted succesfully" % (comment["id"])


if __name__ == "__main__":
  # The "videoid" option specifies the YouTube video ID that uniquely
  # identifies the video for which the comment will be inserted.
  argparser.add_argument("--videoid",
    help="Required; ID for video for which the comment will be inserted.")
  # The "text" option specifies the text that will be used as comment.
  argparser.add_argument("--text", help="Required; text that will be used as comment.")
  args = argparser.parse_args()

  if not args.videoid:
    exit("Please specify videoid using the --videoid= parameter.")
  if not args.text:
    exit("Please specify text using the --text= parameter.")

  youtube = get_authenticated_service(args)
  # All the available methods are used in sequence just for the sake of an example.
  try:
    video_comment_threads = get_comment_threads(youtube, args.videoid)
    parent_id = video_comment_threads[0]["id"]
    insert_comment(youtube, parent_id, args.text)
    video_comments = get_comments(youtube, parent_id)
    update_comment(youtube, video_comments[0])
    set_moderation_status(youtube, video_comments[0])
    mark_as_spam(youtube, video_comments[0])
    delete_comment(youtube, video_comments[0])
  except HttpError, e:
    print "An HTTP error %d occurred:\n%s" % (e.resp.status, e.content)
  else:
    print "Inserted, listed, updated, moderated, marked and deleted comments."
