import os

import google_auth_oauthlib.flow
import googleapiclient.discovery
import googleapiclient.errors


scopes = ['https://www.googleapis.com/auth/yt-analytics.readonly']


def main():
    # Disable OAuthlib's HTTPs verification when running locally.
    # *DO NOT* leave this option enabled when running in production.
    os.environ['OAUTHLIB_INSECURE_TRANSPORT'] = '1'

    # Setup variables for this sample.
    api_service_name = 'youtubeAnalytics'
    api_version = 'v2'
    client_secrets_file = 'YOUR_CLIENT_SECRET_FILE.json'

    # Get credentials and create an API client
    flow = google_auth_oauthlib.flow.InstalledAppFlow.from_client_secrets_file(
        client_secrets_file, scopes)
    credentials = flow.run_console()
    youtube_analytics = googleapiclient.discovery.build(
        api_service_name, api_version, credentials=credentials)

    # Execute the API request.
    request = youtube_analytics.reports().query(
        ids='channel==MINE',
        startDate='2017-01-01',
        endDate='2017-12-31',
        metrics='estimatedMinutesWatched,views,likes,subscribersGained',
        dimensions='day',
        sort='day'
    )
    response = request.execute()

    print(response)


if __name__ == '__main__':
    main()
