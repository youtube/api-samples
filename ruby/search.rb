#!/usr/bin/ruby

require 'rubygems'
require 'google/api_client'
require 'trollop'


# Set DEVELOPER_KEY to the "API key" value from the "Access" tab of the
# {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
# Please ensure that you have enabled the YouTube Data API for your project.
DEVELOPER_KEY = "REPLACE_ME"
YOUTUBE_API_SERVICE_NAME = "youtube"
YOUTUBE_API_VERSION = "v3"

opts = Trollop::options do
  opt :q, 'Search term', :type => String, :default => 'Google'
  opt :maxResults, 'Max results', :type => :int, :default => 25
end

client = Google::APIClient.new(:key => DEVELOPER_KEY,
                               :authorization => nil)
youtube = client.discovered_api(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION)

# Call the search.list method to retrieve results matching the specified
# query term.
opts[:part] = 'id,snippet'
search_response = client.execute!(
  :api_method => youtube.search.list,
  :parameters => opts
)

videos = []
channels = []
playlists = []

# Add each result to the appropriate list, and then display the lists of
# matching videos, channels, and playlists.
search_response.data.items.each do |search_result|
  case search_result.id.kind
    when 'youtube#video'
      videos.push("#{search_result.snippet.title} (#{search_result.id.videoId})")
    when 'youtube#channel'
      channels.push("#{search_result.snippet.title} (#{search_result.id.channelId})")
    when 'youtube#playlist'
      playlists.push("#{search_result.snippet.title} (#{search_result.id.playlistId})")
  end
end

puts "Videos:\n", videos, "\n"
puts "Channels:\n", channels, "\n"
puts "Playlists:\n", playlists, "\n"
