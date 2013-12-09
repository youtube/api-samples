#!/usr/bin/ruby

require 'rubygems'
require 'google/api_client'
# The oauth/oauth_util code is not part of the official Ruby client library. 
# Download it from:
# http://samples.google-api-ruby-client.googlecode.com/git/oauth/oauth_util.rb
require 'oauth/oauth_util'
require 'trollop'


# This code assumes that there's a client_secrets.json file in your current directory,
# which contains OAuth 2.0 information for this application, including client_id and
# client_secret. You can acquire an ID/secret pair from the API Access tab on the
# {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
# For more information about using OAuth2 to access Google APIs, please visit:
#   <https://developers.google.com/accounts/docs/OAuth2>
# For more information about the client_secrets.json file format, please visit:
#   <https://developers.google.com/api-client-library/python/guide/aaa_client_secrets>
# Please ensure that you have enabled the YouTube Data & Analytics APIs for your project.

# These OAuth 2.0 access scopes allow for read-only access to the authenticated
# user's account for both YouTube Data API resources and YouTube Analytics Data.
YOUTUBE_SCOPES = ['https://www.googleapis.com/auth/youtube.readonly',
  'https://www.googleapis.com/auth/yt-analytics.readonly']
YOUTUBE_API_SERVICE_NAME = 'youtube'
YOUTUBE_API_VERSION = 'v3'
YOUTUBE_ANALYTICS_API_SERVICE_NAME = 'youtubeAnalytics'
YOUTUBE_ANALYTICS_API_VERSION = 'v1'

now = Time.new.to_i
SECONDS_IN_DAY = 60 * 60 * 24
SECONDS_IN_WEEK = SECONDS_IN_DAY * 7
one_day_ago = Time.at(now - SECONDS_IN_DAY).strftime('%Y-%m-%d')
one_week_ago = Time.at(now - SECONDS_IN_WEEK).strftime('%Y-%m-%d')

opts = Trollop::options do
  opt :metrics, 'Report metrics', :type => String, :default => 'views,comments,favoritesAdded,favoritesRemoved,likes,dislikes,shares'
  opt :dimensions, 'Report dimensions', :type => String, :default => 'video'
  opt 'start-date', 'Start date, in YYYY-MM-DD format', :type => String, :default => one_week_ago
  opt 'end-date', 'Start date, in YYYY-MM-DD format', :type => String, :default => one_day_ago
  opt 'start-index', 'Start index', :type => :int, :default => 1
  opt 'max-results', 'Max results', :type => :int, :default => 10
  opt :sort, 'Sort order', :type => String, :default => '-views'
end

client = Google::APIClient.new
youtube = client.discovered_api(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION)
youtube_analytics = client.discovered_api(YOUTUBE_ANALYTICS_API_SERVICE_NAME,
  YOUTUBE_ANALYTICS_API_VERSION)

auth_util = CommandLineOAuthHelper.new(YOUTUBE_SCOPES)
client.authorization = auth_util.authorize()

# Retrieve the channel resource for the authenticated user's channel.
channels_response = client.execute!(
  :api_method => youtube.channels.list,
  :parameters => {
    :mine => true,
    :part => 'id'
  }
)

channels_response.data.items.each do |channel|
  opts[:ids] = "channel==#{channel.id}"

  # Call the Analytics API to retrieve a report. For a list of available
  # reports, see:
  # https://developers.google.com/youtube/analytics/v1/channel_reports
  analytics_response = client.execute!(
    :api_method => youtube_analytics.reports.query,
    :parameters => opts
  )

  puts "Analytics Data for Channel #{channel.id}"

  analytics_response.data.columnHeaders.each do |column_header|
    printf '%-20s', column_header.name
  end
  puts

  analytics_response.data.rows.each do |row|
    row.each do |value|
      printf '%-20s', value
    end
    puts
  end
end
