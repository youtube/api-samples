# Sample Ruby code for user authorization

require 'rubygems'
gem 'google-api-client', '>0.7'
require 'google/apis'
require 'google/apis/youtube_v3'
require 'googleauth'
require 'googleauth/stores/file_token_store'

require 'fileutils'
require 'json'

# REPLACE WITH VALID REDIRECT_URI FOR YOUR CLIENT
REDIRECT_URI = 'http://localhost'
APPLICATION_NAME = 'YouTube Data API Ruby Tests'

# REPLACE WITH NAME/LOCATION OF YOUR client_secrets.json FILE
CLIENT_SECRETS_PATH = 'client_secret.json'

# REPLACE FINAL ARGUMENT WITH FILE WHERE CREDENTIALS WILL BE STORED
CREDENTIALS_PATH = File.join(Dir.home, '.credentials',
                             "youtube-quickstart-ruby-credentials.yaml")

# SCOPE FOR WHICH THIS SCRIPT REQUESTS AUTHORIZATION
SCOPE = Google::Apis::YoutubeV3::AUTH_YOUTUBE_READONLY

def authorize
  FileUtils.mkdir_p(File.dirname(CREDENTIALS_PATH))

  client_id = Google::Auth::ClientId.from_file(CLIENT_SECRETS_PATH)
  token_store = Google::Auth::Stores::FileTokenStore.new(file: CREDENTIALS_PATH)
  authorizer = Google::Auth::UserAuthorizer.new(
    client_id, SCOPE, token_store)
  user_id = 'default'
  credentials = authorizer.get_credentials(user_id)
  if credentials.nil?
    url = authorizer.get_authorization_url(base_url: REDIRECT_URI)
    puts "Open the following URL in the browser and enter the " +
         "resulting code after authorization"
    puts url
    code = gets
    credentials = authorizer.get_and_store_credentials_from_code(
      user_id: user_id, code: code, base_url: REDIRECT_URI)
  end
  credentials
end

# Initialize the API
service = Google::Apis::YoutubeV3::YouTubeService.new
service.client_options.application_name = APPLICATION_NAME
service.authorization = authorize

# Sample ruby code for channels.list

def channels_list_by_username(service, part, **params)
  response = service.list_channels(part, params).to_json
  item = JSON.parse(response).fetch("items")[0]

  puts ("This channel's ID is #{item.fetch("id")}. " +
        "Its title is '#{item.fetch("snippet").fetch("title")}', and it has " +
        "#{item.fetch("statistics").fetch("viewCount")} views.")
end

channels_list_by_username(service, 'snippet,contentDetails,statistics', for_username: 'GoogleDevelopers')
