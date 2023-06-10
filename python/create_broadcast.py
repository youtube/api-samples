#!/usr/bin/python

# Schedule live stream on your YouTube channel
# Sample usage:
#   python create_broadcast.py --broadcast_title="Hi all!" --privacy_status="public"

import json
import argparse
from datetime import datetime, timedelta

from googleapiclient.discovery import build
from googleapiclient.errors import HttpError
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow

# The CLIENT_SECRETS_FILE variable specifies the name of a file that contains
# the OAuth 2.0 information for this application, including its client_id and
# client_secret. You can acquire an OAuth 2.0 client ID and client secret from
# the {{ Google Cloud Console }} at 
#   https://console.cloud.google.com/apis/
# Please ensure that you have enabled the YouTube Data API for your project.
# For more information about using OAuth2 to access the YouTube Data API, see:
#   https://developers.google.com/youtube/v3/guides/authentication
# For more information about the client_secrets.json file format, see:
#   https://googleapis.github.io/google-api-python-client/docs/client-secrets.html
CLIENT_SECRETS_FILE = "client_secrets.json"

# The CREDENTIALS_FILE variable specifies the name of a file that contains
# the refresh_token which allows you to have short-lived access tokens without
# having to collect credentials every time one expires.
CREDENTIALS_FILE = "credentials.json"

# This OAuth 2.0 access scope allows for full read/write access to the
# authenticated user's account.
YOUTUBE_READ_WRITE_SCOPE = "https://www.googleapis.com/auth/youtube"
YOUTUBE_API_SERVICE_NAME = "youtube"
YOUTUBE_API_VERSION = "v3"

SCOPES = [YOUTUBE_READ_WRITE_SCOPE]

def get_saved_credentials(filename=CREDENTIALS_FILE):
    """ Read in any saved OAuth data/tokens """
    fileData = {}
    try:
        with open(filename, 'r') as file:
            fileData: dict = json.load(file)
    except FileNotFoundError:
        return None
    if fileData and 'refresh_token' in fileData and 'client_id' in fileData and 'client_secret' in fileData:
        return Credentials(**fileData)
    return None

def store_creds(credentials, filename=CREDENTIALS_FILE):
    """ Save refresh_token with other credentials in the file """
    if not isinstance(credentials, Credentials):
        return
    fileData = {'refresh_token': credentials.refresh_token,
                'token': credentials.token,
                'client_id': credentials.client_id,
                'client_secret': credentials.client_secret,
                'token_uri': credentials.token_uri}
    with open(filename, 'w') as file:
        json.dump(fileData, file, indent=" "*4)
    print(f'Credentials serialized to {filename}.')

def get_credentials_via_oauth(filename=CLIENT_SECRETS_FILE, scopes=SCOPES, saveData=True) -> Credentials:
    """ Use data in the given filename to get oauth data """
    iaflow = InstalledAppFlow.from_client_secrets_file(filename, scopes)
    iaflow.run_local_server()
    if saveData:
        store_creds(iaflow.credentials)
    return iaflow.credentials

def get_service(credentials, service=YOUTUBE_API_SERVICE_NAME, version=YOUTUBE_API_VERSION):
    """ Construct a Resource for interacting with an YouTube API. """
    return build(service, version, credentials=credentials)

def insert_stream(youtube, options):
    """ Create a liveStream resource and set its title, description, format, and ingestion type.\n
        This resource describes the content that you are transmitting to YouTube. """
    request = youtube.liveStreams().insert(
        part="snippet,cdn",
        body={
          "cdn": {
            "format": options.stream_format,
            "ingestionType": "rtmp"
          },
          "snippet": {
            "title": options.stream_title,
            "description": options.stream_description,
          }
        }
    )
    response = request.execute()
    print("Stream '{0}' with title '{1}' was inserted.".format(response["id"], response["snippet"]["title"]))
    return response["id"]

def insert_broadcast(youtube, options):
    """ Create a liveBroadcast resource and set its title, description, 
        scheduled start time, scheduled end time, and privacy status. """
    request = youtube.liveBroadcasts().insert(
        part="snippet,status",
        body={
          "snippet": {
            "title": options.broadcast_title,
            "description": options.broadcast_description,
            "scheduledStartTime": options.start_time,
            "scheduledEndTime": options.end_time
          },
          "status": {
            "privacyStatus": options.privacy_status
          }
        }
    )
    response = request.execute()
    print("Broadcast '{0}' with title '{1}' was published at '{2}'.".format(response["id"], response["snippet"]["title"], response["snippet"]["publishedAt"]))
    return response["id"]

def bind_broadcast(youtube, broadcast_id, stream_id):
    """ Bind the broadcast to the video stream. By doing so, you link the video that 
        you will transmit to YouTube to the broadcast that the video is for. """
    request = youtube.liveBroadcasts().bind(
        part="id,contentDetails",
        id=broadcast_id,
        streamId=stream_id
    )
    response = request.execute()
    print("Broadcast '{0}' was bound to the stream '{1}'.".format(
        response["id"], response["contentDetails"]["boundStreamId"]))

if __name__ == "__main__":
    parser = argparse.ArgumentParser(prog='Broadcast Binder')

    parser.add_argument("--broadcast-title", dest="broadcast_title", help="Broadcast title", default="New Broadcast")
    parser.add_argument("--broadcast-desc", dest="broadcast_description", help="Broadcast description", default=" ")
    parser.add_argument("--privacy-status", dest="privacy_status", help="Broadcast privacy status", default="private")
    parser.add_argument("--start-time", dest="start_time", help="Scheduled start time", 
                        default=datetime.now().isoformat())
    parser.add_argument("--end-time", dest="end_time", help="Scheduled end time",
                        default=(datetime.now() + timedelta(days=1)).isoformat())
    parser.add_argument("--stream-title", dest="stream_title", help="Stream title", default="New Stream")
    parser.add_argument("--stream-desc", dest="stream_description", help="Stream description", default=" ")
    parser.add_argument("--stream-format", dest="stream_format", help="Stream format", default="1080p")

    args = parser.parse_args()

    credentials = get_saved_credentials()
    if not credentials:
        credentials = get_credentials_via_oauth()
        
    youtube = get_service(credentials)
    try:
        broadcast_id = insert_broadcast(youtube, args)
        stream_id = insert_stream(youtube, args)
        bind_broadcast(youtube, broadcast_id, stream_id)
    except HttpError as error:
        print("An HTTP error {0} occured:\n{1}".format(error.status_code, error.content))