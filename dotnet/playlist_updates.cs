using System;
using System.Collections;
using System.Collections.Generic;
using System.Reflection;

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
  class playlist_updates
  {
    static void Main(string[] args)
    {
      CommandLine.EnableExceptionHandling();
      CommandLine.DisplayGoogleSampleHeader("YouTube Data API: Playlist Updates");

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

      var newPlaylist = new Playlist();
      newPlaylist.Snippet = new PlaylistSnippet();
      newPlaylist.Snippet.Title = "Test Playlist";
      newPlaylist.Snippet.Description = "A playlist created with the YouTube API v3";
      newPlaylist.Status = new PlaylistStatus();
      newPlaylist.Status.PrivacyStatus = "public";
      newPlaylist = youtube.Playlists.Insert(newPlaylist, "snippet,status").Fetch();

      var newPlaylistItem = new PlaylistItem();
      newPlaylistItem.Snippet = new PlaylistItemSnippet();
      newPlaylistItem.Snippet.PlaylistId = newPlaylist.Id;
      newPlaylistItem.Snippet.ResourceId = new ResourceId();
      newPlaylistItem.Snippet.ResourceId.Kind = "youtube#video";
      newPlaylistItem.Snippet.ResourceId.VideoId = "GNRMeaz6QRI";
      newPlaylistItem = youtube.PlaylistItems.Insert(newPlaylistItem, "snippet").Fetch();

      CommandLine.WriteLine(String.Format("Playlist item id {0} was added to playlist id {1}.", newPlaylistItem.Id, newPlaylist.Id));

      CommandLine.PressAnyKeyToExit();
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
        state = AuthorizationMgr.RequestNativeAuthorization(client, YoutubeService.Scopes.Youtube.GetStringValue());
        AuthorizationMgr.SetCachedRefreshToken(storage, key, state);
      }

      return state;
    }
  }
}
