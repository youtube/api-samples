#!/usr/bin/ruby

require 'rubygems'
require 'google/api_client'
# The oauth/oauth_util code is not part of the official Ruby client library. 
# Download it from:
# http://samples.google-api-ruby-client.googlecode.com/git/oauth/oauth_util.rb
require 'oauth/oauth_util'


# This OAuth 2.0 access scope allows for full read/write access to the
# authenticated user's account.
YOUTUBE_SCOPE = 'https://www.googleapis.com/auth/youtube'
YOUTUBE_API_SERVICE_NAME = 'youtube'
YOUTUBE_API_VERSION = 'v3'

client = Google::APIClient.new
youtube = client.discovered_api(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION)

auth_util = CommandLineOAuthHelper.new(YOUTUBE_SCOPE)
client.authorization = auth_util.authorize()

body = {
  :snippet => {
    :resourceId => {
      :kind => 'youtube#channel',
      # Replace the value below with the ID of the channel being subscribed to.
      :channelId => 'UCtVd0c0tGXuTSbU5d8cSBUg'
    }
  }
}

# Call the API's youtube.subscriptions.insert method to add the subscription
# to the specified channel.
begin
  subscriptions_response = client.execute!(
    :api_method => youtube.subscriptions.insert,
    :parameters => {
      :part => body.keys.join(',')
    },
    :body_object => body
  )
  puts "A subscription to '#{subscriptions_response.data.snippet.title}' was added."
rescue Google::APIClient::ClientError => e
  puts "#{e}: Unable to add subscription. Are you already subscribed?"
end
