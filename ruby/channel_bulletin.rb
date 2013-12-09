#!/usr/bin/ruby

require 'rubygems'
require 'google/api_client'
# The oauth/oauth_util code is not part of the official Ruby client library. 
# Download it from:
# http://samples.google-api-ruby-client.googlecode.com/git/oauth/oauth_util.rb
require 'oauth/oauth_util'
require 'trollop'


# This OAuth 2.0 access scope allows for full read/write access to the
# authenticated user's account.
YOUTUBE_SCOPE = 'https://www.googleapis.com/auth/youtube'
YOUTUBE_API_SERVICE_NAME = 'youtube'
YOUTUBE_API_VERSION = 'v3'

client = Google::APIClient.new
youtube = client.discovered_api(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION)

auth_util = CommandLineOAuthHelper.new(YOUTUBE_SCOPE)
client.authorization = auth_util.authorize()

opts = Trollop::options do
  opt :message, 'Required text of message to post.', :type => String
  opt :videoid, 'Optional ID of video to post.', :type => String
  opt :playlistid, 'Optional ID of playlist to post.', :type => String
end

# You can post a message with or without an accompanying video or playlist.
# However, you can't post a video and a playlist at the same time.

if opts[:videoid] and opts[:playlistid]
  Trollop::die 'You cannot post a video and a playlist at the same time'
end

Trollop::die :message, 'is required' unless opts[:message]

body = {
  :snippet => {
    :description => opts[:message]
  }
}

if opts[:videoid]
  body[:contentDetails] = {
    :bulletin => {
      :resourceId => {
        :kind => 'youtube#video',
        :videoId => opts[:videoid]
      }
    }
  }
end

if opts[:playlistid]
  body[:contentDetails] = {
    :bulletin => {
      :resourceId => {
        :kind => 'youtube#playlist',
        :playlistId => opts[:playlistid]
      }
    }
  }
end

# Call the API's youtube.activities.insert method to post the channel bulletin.
client.execute!(
  :api_method => youtube.activities.insert,
  :parameters => {
    :part => body.keys.join(',')
  },
  :body_object => body
)
puts 'The bulletin was posted to your channel.'
