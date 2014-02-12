#!/usr/bin/ruby

require 'rubygems'
gem 'google-api-client', '>0.7'
require 'google/api_client'
require 'google/api_client/client_secrets'
require 'google/api_client/auth/file_storage'
require 'google/api_client/auth/installed_app'
require 'trollop'

# This OAuth 2.0 access scope allows for full read/write access to the
# authenticated user's account.
YOUTUBE_SCOPE = 'https://www.googleapis.com/auth/youtube'
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
      :scope => [YOUTUBE_SCOPE]
    )
    client.authorization = flow.authorize(file_storage)
  else
    client.authorization = file_storage.authorization
  end

  return client, youtube
end

def main
  opts = Trollop::options do
    opt :message, 'Required text of message to post.', :type => String
    opt :video_id, 'Optional ID of video to post.', :type => String
    opt :playlist_id, 'Optional ID of playlist to post.', :type => String
  end

  # You can post a message with or without an accompanying video or playlist.
  # However, you can't post a video and a playlist at the same time.
  if opts[:video_id] and opts[:playlist_id]
    Trollop::die 'You cannot post a video and a playlist at the same time'
  end
  Trollop::die :message, 'is required' unless opts[:message]

  client, youtube = get_authenticated_service

  begin
    body = {
      :snippet => {
        :description => opts[:message]
      }
    }

    if opts[:video_id]
      body[:contentDetails] = {
        :bulletin => {
          :resourceId => {
            :kind => 'youtube#video',
            :videoId => opts[:video_id]
          }
        }
      }
    end

    if opts[:playlist_id]
      body[:contentDetails] = {
        :bulletin => {
          :resourceId => {
            :kind => 'youtube#playlist',
            :playlistId => opts[:playlist_id]
          }
        }
      }
    end

    # Call the youtube.activities.insert method to post the channel bulletin.
    client.execute!(
      :api_method => youtube.activities.insert,
      :parameters => {
        :part => body.keys.join(',')
      },
      :body_object => body
    )

    puts "The bulletin was posted to your channel."
  rescue Google::APIClient::TransmissionError => e
    puts e.result.body
  end
end

main
