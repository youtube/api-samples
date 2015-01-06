#!/usr/bin/ruby

require 'rubygems'
require 'google/api_client'
require 'google/api_client/client_secrets'
require 'google/api_client/auth/file_storage'
require 'google/api_client/auth/installed_app'
require 'certified'


class Youtube_Helper

  @@client_email = '' #email id from service account (that really long email address...)
  @@youtube_email = '' #email associated with youtube account
  @@p12_file_path = '' #path to the file downloaded from the service account (Generate new p12 key button)
  @@p12_password = '' # password to the file usually 'notasecret'
  @@api_key = nil # The API key for non authenticated things like lists
  YOUTUBE_UPLOAD_SCOPE = 'https://www.googleapis.com/auth/youtube.upload'
  YOUTUBE_API_SERVICE_NAME = 'youtube'
  YOUTUBE_API_VERSION = 'v3'
  @@client = nil
  @@youtube = nil
  FILE_POSTFIX = '-oauth2.json'

  def initialize(client_email, youtube_email, p12_file_path, p12_password, api_key)
    @@client_email=client_email
    @@youtube_email=youtube_email
    @@p12_file_path=p12_file_path
    @@p12_password=p12_password
    @@api_key = api_key
  end

  def get_authenticated_service

    
    credentialsFile = $0 + FILE_POSTFIX

    needtoauthenticate = false

    @api_client = Google::APIClient.new(
      :application_name => $PROGRAM_NAME,
      :application_version => '1.0.0'
    )

    key = Google::APIClient::KeyUtils.load_from_pkcs12(@@p12_file_path, @@p12_password)
    @auth_client = Signet::OAuth2::Client.new(
        :token_credential_uri => 'https://accounts.google.com/o/oauth2/token',
        :audience => 'https://accounts.google.com/o/oauth2/token',
        :scope => YOUTUBE_UPLOAD_SCOPE,
        :issuer => @@client_email,
        :person => @@youtube_email,
        :signing_key => key)


    if File.exist? credentialsFile
      puts 'credential file exists'
      puts credentialsFile.to_s
      File.open(credentialsFile, 'r') do |file|
        credentials = JSON.load(file)
        if !credentials.nil?
          puts 'get credentials from file'
          @auth_client.access_token = credentials['access_token']
          @auth_client.client_id = credentials['client_id']
          @auth_client.client_secret = credentials['client_secret']
          @auth_client.refresh_token = credentials['refresh_token']
          @auth_client.expires_in = (Time.parse(credentials['token_expiry']) - Time.now).ceil
          @api_client.authorization = @auth_client
          if @auth_client.expired?
            puts 'authorization expired'
            needtoauthenticate = true
          end
        else
          needtoauthenticate = true
        end
      end
    else
      needtoauthenticate = true
    end

    if needtoauthenticate
      @auth_client.fetch_access_token!
      @api_client.authorization = @auth_client
      save(credentialsFile)
    end

    youtube = @api_client.discovered_api(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION)
    @@client = @api_client
    @@youtube = youtube
    return @api_client, youtube
  end

  def save(credentialsFile)
    File.open(credentialsFile, 'w', 0600) do |file|
      json = JSON.dump({
        :access_token => @auth_client.access_token,
        :client_id => @auth_client.client_id,
        :client_secret => @auth_client.client_secret,
        :refresh_token => @auth_client.refresh_token,
        :token_expiry => @auth_client.expires_at
      })
      file.write(json)
    end
  end

  def upload2youtube file, title, description, category_id, keywords, privacy_status
    puts 'begin'
    begin
      body = {
        :snippet => {
          :title => title,
          :description => description,
          :tags => keywords.split(','),
          :categoryId => category_id,
        },
        :status => {
          :privacyStatus => privacy_status
        }
      }
      puts body.keys.join(',')

      # Call the API's videos.insert method to create and upload the video.
      videos_insert_response = @@client.execute!(
        :api_method => @@youtube.videos.insert,
        :body_object => body,
        :media => Google::APIClient::UploadIO.new(file, 'video/*'),
        :parameters => {
          'uploadType' => 'multipart',
          :part => body.keys.join(',')
        }
      )

      puts'inserted'
      
      puts "'#{videos_insert_response.data.snippet.title}' (video id: #{videos_insert_response.data.id}) was successfully uploaded."

    rescue Google::APIClient::TransmissionError => e
      puts e.result.body
    end

    return videos_insert_response.data.id #video id
    
  end

  def upload_thumbnail  video_id, thumbnail_file, thumbnail_size
    puts 'uploading thumbnail'
    begin
      thumbnail_upload_response = @@client.execute({ :api_method => @@youtube.thumbnails.set,
                            :parameters => { :videoId => video_id,
                                             'uploadType' => 'media',
                                             :onBehalfOfContentOwner => @@youtube_email},
                            :media => thumbnail_file,
                            :headers => { 'Content-Length' => thumbnail_size.to_s,
                                          'Content-Type' => 'image/jpg' }
                            })
    rescue Google::APIClient::TransmissionError => e
        puts e.result.body 
    end 
  end

  def get_video_statistics video_id
    client = Google::APIClient.new(:key => @@api_key,
                                    :application_name => $PROGRAM_NAME,
                                    :application_version => '1.0.0',
                                    :authorization => nil)
    youtube = client.discovered_api(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION)
    stats_response = client.execute!( :api_method => youtube.videos.list,
                    :parameters => {:part => 'statistics', :id => video_id }
                    )
    return stats_response
  end
end
