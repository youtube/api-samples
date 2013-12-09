#!/usr/bin/ruby

require 'rubygems'
require 'google/api_client'
# The oauth/oauth_util code is not part of the official Ruby client library. 
# Download it from:
# http://samples.google-api-ruby-client.googlecode.com/git/oauth/oauth_util.rb
require 'oauth/oauth_util'


# This OAuth 2.0 access scope allows for read-only access to the authenticated
# user's account, but not other types of account access.
YOUTUBE_READONLY_SCOPE = 'https://www.googleapis.com/auth/youtube.readonly'
YOUTUBE_API_SERVICE_NAME = 'youtube'
YOUTUBE_API_VERSION = 'v3'

client = Google::APIClient.new
youtube = client.discovered_api(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION)

auth_util = CommandLineOAuthHelper.new(YOUTUBE_READONLY_SCOPE)
client.authorization = auth_util.authorize()

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
  playlistitems_response = client.execute!(
    :api_method => youtube.playlist_items.list,
    :parameters => {
      :playlistId => uploads_list_id,
      :part => 'snippet',
      :maxResults => 50
    }
  )

  puts "Videos in list #{uploads_list_id}"

  # Print information about each video.
  playlistitems_response.data.items.each do |playlist_item|
    title = playlist_item['snippet']['title']
    video_id = playlist_item['snippet']['resourceId']['videoId']

    puts "#{title} (#{video_id})"
  end

  puts
end
