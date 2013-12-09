using System;
using System.Collections;
using System.Collections.Generic;
using System.IO;
using System.Reflection;
using System.Threading;

/*
 * External dependencies, OAuth 2.0 support, and core client libraries are at:
 *   https://code.google.com/p/google-api-dotnet-client/wiki/APIs#YouTube_Data_API
 * Also see the Samples.zip file for the Google.Apis.Samples.Helper classes at:
 *   https://code.google.com/p/google-api-dotnet-client/wiki/Downloads
 */

using DotNetOpenAuth.OAuth2;

using Google.Apis.Authentication;
using Google.Apis.Authentication.OAuth2;
using Google.Apis.Authentication.OAuth2.DotNetOpenAuth;
using Google.Apis.Samples.Helper;
using Google.Apis.Services;
using Google.Apis.Util;
using Google.Apis.Youtube.v3;
using Google.Apis.Youtube.v3.Data;

namespace dotnet
{
  class upload_video
  {
    static void Main(string[] args)
    {
      CommandLine.EnableExceptionHandling();
      CommandLine.DisplayGoogleSampleHeader("YouTube Data API: Upload Video");

      var credentials = PromptingClientCredentials.EnsureFullClientCredentials();
      var provider = new NativeApplicationClient(GoogleAuthenticationServer.Description)
      {
        ClientIdentifier = credentials.ClientId,
        ClientSecret = credentials.ClientSecret
      };
      var auth = new OAuth2Authenticator<NativeApplicationClient>(provider, GetAuthorization);

      var youtube = new YoutubeService(new BaseClientService.Initializer()
      {
        Authenticator = auth
      });

      var video = new Video();
      video.Snippet = new VideoSnippet();
      video.Snippet.Title = CommandLine.RequestUserInput<string>("Video title");
      video.Snippet.Description = CommandLine.RequestUserInput<string>("Video description");
      video.Snippet.Tags = new string[] { "tag1", "tag2" };
      // See https://developers.google.com/youtube/v3/docs/videoCategories/list
      video.Snippet.CategoryId = "22";
      video.Status = new VideoStatus();
      video.Status.PrivacyStatus = CommandLine.RequestUserInput<string>("Video privacy (public, private, or unlisted)");
      var filePath = CommandLine.RequestUserInput<string>("Path to local video file");
      var fileStream = new FileStream(filePath, FileMode.Open);

      var videosInsertRequest = youtube.Videos.Insert(video, "snippet,status", fileStream, "video/*");
      videosInsertRequest.ProgressChanged += videosInsertRequest_ProgressChanged;
      videosInsertRequest.ResponseReceived += videosInsertRequest_ResponseReceived;

      var uploadThread = new Thread(() => videosInsertRequest.Upload());
      uploadThread.Start();
      uploadThread.Join();

      CommandLine.PressAnyKeyToExit();
    }

    static void videosInsertRequest_ProgressChanged(Google.Apis.Upload.IUploadProgress obj)
    {
      CommandLine.WriteLine(String.Format("{0} bytes sent.", obj.BytesSent));
    }

    static void videosInsertRequest_ResponseReceived(Video obj)
    {
      CommandLine.WriteLine(String.Format("Video id {0} was successfully uploaded.", obj.Id));
    }

    private static IAuthorizationState GetAuthorization(NativeApplicationClient client)
    {
      var storage = MethodBase.GetCurrentMethod().DeclaringType.ToString();
      var key = "storage_key";

      IAuthorizationState state = AuthorizationMgr.GetCachedRefreshToken(storage, key);
      if (state != null)
      {
        client.RefreshToken(state);
      }
      else
      {
        state = AuthorizationMgr.RequestNativeAuthorization(client, YoutubeService.Scopes.YoutubeUpload.GetStringValue());
        AuthorizationMgr.SetCachedRefreshToken(storage, key, state);
      }

      return state;
    }
  }
}
