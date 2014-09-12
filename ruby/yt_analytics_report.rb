#!/usr/bin/ruby

require 'rubygems'
gem 'google-api-client', '>0.7'
require 'google/api_client'
require 'google/api_client/client_secrets'
require 'google/api_client/auth/file_storage'
require 'google/api_client/auth/installed_app'
require 'trollop'

# These OAuth 2.0 access scopes allow for read-only access to the authenticated
# user's account for both YouTube Data API resources and YouTube Analytics Data.
YOUTUBE_SCOPES = ['https://www.googleapis.com/auth/youtube.readonly',
  'https://www.googleapis.com/auth/yt-analytics.readonly']
YOUTUBE_API_SERVICE_NAME = 'youtube'
YOUTUBE_API_VERSION = 'v3'
YOUTUBE_ANALYTICS_API_SERVICE_NAME = 'youtubeAnalytics'
YOUTUBE_ANALYTICS_API_VERSION = 'v1'

def get_authenticated_services
  client = Google::APIClient.new(
    :application_name => $PROGRAM_NAME,
    :application_version => '1.0.0'
  )
  youtube = client.discovered_api(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION)
  youtube_analytics = client.discovered_api(YOUTUBE_ANALYTICS_API_SERVICE_NAME, YOUTUBE_ANALYTICS_API_VERSION)

  file_storage = Google::APIClient::FileStorage.new("#{$PROGRAM_NAME}-oauth2.json")
  if file_storage.authorization.nil?
    client_secrets = Google::APIClient::ClientSecrets.load
    flow = Google::APIClient::InstalledAppFlow.new(
      :client_id => client_secrets.client_id,
      :client_secret => client_secrets.client_secret,
      :scope => YOUTUBE_SCOPES
    )
    client.authorization = flow.authorize(file_storage)
  else
    client.authorization = file_storage.authorization
  end

  return client, youtube, youtube_analytics
end

def main
  now = Time.new.to_i
  seconds_in_day = 60 * 60 * 24
  seconds_in_week = seconds_in_day * 7
  one_day_ago = Time.at(now - seconds_in_day).strftime('%Y-%m-%d')
  one_week_ago = Time.at(now - seconds_in_week).strftime('%Y-%m-%d')

  opts = Trollop::options do
    opt :metrics, 'Report metrics', :type => String, :default => 'views,comments,favoritesAdded,favoritesRemoved,likes,dislikes,shares'
    opt :dimensions, 'Report dimensions', :type => String, :default => 'video'
    opt 'start-date', 'Start date, in YYYY-MM-DD format', :type => String, :default => one_week_ago
    opt 'end-date', 'Start date, in YYYY-MM-DD format', :type => String, :default => one_day_ago
    opt 'start-index', 'Start index', :type => :int, :default => 1
    opt 'max-results', 'Max results', :type => :int, :default => 10
    opt :sort, 'Sort order', :type => String, :default => '-views'
  end

  client, youtube, youtube_analytics = get_authenticated_services

  begin
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
  rescue Google::APIClient::TransmissionError => e
    puts e.result.body
  end
end

main
