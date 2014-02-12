#!/usr/bin/ruby

require 'rubygems'
gem 'google-api-client', '>0.7'
require 'google/api_client'
require 'google/api_client/client_secrets'
require 'google/api_client/auth/file_storage'
require 'google/api_client/auth/installed_app'
require 'trollop'

# A limited OAuth 2 access scope that allows for uploading files, but not other
# types of account access.
YOUTUBE_UPLOAD_SCOPE = 'https://www.googleapis.com/auth/youtube.upload'
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
      :scope => [YOUTUBE_UPLOAD_SCOPE]
    )
    client.authorization = flow.authorize(file_storage)
  else
    client.authorization = file_storage.authorization
  end

  return client, youtube
end

def main
  opts = Trollop::options do
    opt :file, 'Video file to upload', :type => String
    opt :title, 'Video title', :default => 'Test Title', :type => String
    opt :description, 'Video description',
          :default => 'Test Description', :type => String
    opt :category_id, 'Numeric video category. See https://developers.google.com/youtube/v3/docs/videoCategories/list',
          :default => 22, :type => :int
    opt :keywords, 'Video keywords, comma-separated',
          :default => '', :type => String
    opt :privacy_status, 'Video privacy status: public, private, or unlisted',
          :default => 'public', :type => String
  end

  if opts[:file].nil? or not File.file?(opts[:file])
    Trollop::die :file, 'does not exist'
  end

  client, youtube = get_authenticated_service

  begin
    body = {
      :snippet => {
        :title => opts[:title],
        :description => opts[:description],
        :tags => opts[:keywords].split(','),
        :categoryId => opts[:category_id],
      },
      :status => {
        :privacyStatus => opts[:privacy_status]
      }
    }

    videos_insert_response = client.execute!(
      :api_method => youtube.videos.insert,
      :body_object => body,
      :media => Google::APIClient::UploadIO.new(opts[:file], 'video/*'),
      :parameters => {
        :uploadType => 'resumable',
        :part => body.keys.join(',')
      }
    )

    videos_insert_response.resumable_upload.send_all(client)

    puts "Video id '#{videos_insert_response.data.id}' was successfully uploaded."
  rescue Google::APIClient::TransmissionError => e
    puts e.result.body
  end
end

main
