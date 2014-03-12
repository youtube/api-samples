require 'google/api_client'
require 'google/api_client/client_secrets'
require 'json'
require 'launchy'
require 'thin'

RESPONSE_HTML = <<stop
<html>
  <head>
    <title>OAuth 2 Flow Complete</title>
  </head>
  <body>
    You have successfully completed the OAuth 2 flow. Please close this browser window and return to your program.
  </body>
</html>
stop

FILE_POSTFIX = '-oauth2.json'

# Small helper for the sample apps for performing OAuth 2.0 flows from the command
# line. Starts an embedded server to handle redirects.
class CommandLineOAuthHelper

  def initialize(scope)
    credentials = Google::APIClient::ClientSecrets.load
    @authorization = Signet::OAuth2::Client.new(
      :authorization_uri => credentials.authorization_uri,
      :token_credential_uri => credentials.token_credential_uri,
      :client_id => credentials.client_id,
      :client_secret => credentials.client_secret,
      :redirect_uri => credentials.redirect_uris.first,
      :scope => scope
    )
  end

  # Request authorization. Checks to see if a local file with credentials is present, and uses that.
  # Otherwise, opens a browser and waits for response, then saves the credentials locally.
  def authorize
    credentialsFile = $0 + FILE_POSTFIX

    if File.exist? credentialsFile
      File.open(credentialsFile, 'r') do |file|
        credentials = JSON.load(file)
        @authorization.access_token = credentials['access_token']
        @authorization.client_id = credentials['client_id']
        @authorization.client_secret = credentials['client_secret']
        @authorization.refresh_token = credentials['refresh_token']
        @authorization.expires_in = (Time.parse(credentials['token_expiry']) - Time.now).ceil
        if @authorization.expired?
          @authorization.fetch_access_token!
          save(credentialsFile)
        end
      end
    else
      auth = @authorization
      url = @authorization.authorization_uri().to_s
      server = Thin::Server.new('0.0.0.0', 8080) do
        run lambda { |env|
          # Exchange the auth code & quit
          req = Rack::Request.new(env)
          auth.code = req['code']
          auth.fetch_access_token!
          server.stop()
          [200, {'Content-Type' => 'text/html'}, RESPONSE_HTML]
        }
      end

      Launchy.open(url)
      server.start()

      save(credentialsFile)
    end

    return @authorization
  end

  def save(credentialsFile)
    File.open(credentialsFile, 'w', 0600) do |file|
      json = JSON.dump({
        :access_token => @authorization.access_token,
        :client_id => @authorization.client_id,
        :client_secret => @authorization.client_secret,
        :refresh_token => @authorization.refresh_token,
        :token_expiry => @authorization.expires_at
      })
      file.write(json)
    end
  end
end
