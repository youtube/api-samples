#!/usr/bin/ruby

require 'rubygems'
gem 'google-api-client', '>0.7'
require 'google/api_client'
require 'google/api_client/client_secrets'
require 'google/api_client/auth/file_storage'
require 'google/api_client/auth/installed_app'

# This OAuth 2.0 access scope allows for read-only access to the authenticated
# user's account, but not other types of account access.
YOUTUBE_READONLY_SCOPE = 'https://www.googleapis.com/auth/youtube.readonly'
YOUTUBE_API_SERVICE_NAME = 'youtube'
YOUTUBE_API_VERSION = 'v3'

def get_authenticated_service
  client = Google::APIClient.new(
    :application_name => $PROGRAM_NAME,
    :application_version => '1.0.0'
  )
  youtube = client.discovered_api(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION)

  file_storage = Google::APIClient::FileStorage.new("#{$PROGRAM_NAME}-oauth2.json")
  if file_storage.authorization.nil?
    client_secrets = Google::APIClient::ClientSecrets.load
    flow = Google::APIClient::InstalledAppFlow.new(
      :client_id => client_secrets.client_id,
      :client_secret => client_secrets.client_secret,
      :scope => [YOUTUBE_READONLY_SCOPE]
    )
    client.authorization = flow.authorize(file_storage)
  else
    client.authorization = file_storage.authorization
  end

  return client, youtube
end

def main
  client, youtube = get_authenticated_service

  begin
    # Retrieve the "contentDetails" part of the channel resource for the
    # authenticated user's channel.
    channels_response = client.execute!(
      :api_method => youtube.channels.list,
      :parameters => {
        :mine => true,
        :part => 'contentDetails'
      }
    )

    channels_response.data.items.each do |channel|
      # From the API response, extract the playlist ID that identifies the list
      # of videos uploaded to the authenticated user's channel.
      uploads_list_id = channel['contentDetails']['relatedPlaylists']['uploads']

      # Retrieve the list of videos uploaded to the authenticated user's channel.
      next_page_token = ''
      until next_page_token.nil?
        playlistitems_response = client.execute!(
          :api_method => youtube.playlist_items.list,
          :parameters => {
            :playlistId => uploads_list_id,
            :part => 'snippet',
            :maxResults => 50,
            :pageToken => next_page_token
          }
        )

        puts "Videos in list #{uploads_list_id}"

        # Print information about each video.
        playlistitems_response.data.items.each do |playlist_item|
          title = playlist_item['snippet']['title']
          video_id = playlist_item['snippet']['resourceId']['videoId']

          puts "#{title} (#{video_id})"
        end

        next_page_token = playlistitems_response.next_page_token
      end

      puts
    end
  rescue Google::APIClient::TransmissionError => e
    puts e.result.body
  end
end

main
