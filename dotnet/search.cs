using System;
using System.Collections;
using System.Collections.Generic;

/*
 * External dependencies, OAuth 2.0 support, and core client libraries are at:
 *   https://code.google.com/p/google-api-dotnet-client/wiki/APIs#YouTube_Data_API
 * Also see the Samples.zip file for the Google.Apis.Samples.Helper classes at:
 *   https://code.google.com/p/google-api-dotnet-client/wiki/Downloads
 */

using Google.Apis.Samples.Helper;
using Google.Apis.Services;
using Google.Apis.Youtube.v3;
using Google.Apis.Youtube.v3.Data;

namespace dotnet
{
  class search
  {
    static void Main(string[] args)
    {
      CommandLine.EnableExceptionHandling();
      CommandLine.DisplayGoogleSampleHeader("YouTube Data API: Search");

      SimpleClientCredentials credentials = PromptingClientCredentials.EnsureSimpleClientCredentials();

      YoutubeService youtube = new YoutubeService(new BaseClientService.Initializer() {
        ApiKey = credentials.ApiKey
      });

      SearchResource.ListRequest listRequest = youtube.Search.List("snippet");
      listRequest.Q = CommandLine.RequestUserInput<string>("Search term: ");
      listRequest.Order = SearchResource.Order.Relevance;

      SearchListResponse searchResponse = listRequest.Fetch();

      List<string> videos = new List<string>();
      List<string> channels = new List<string>();
      List<string> playlists = new List<string>();

      foreach (SearchResult searchResult in searchResponse.Items)
      {
        switch (searchResult.Id.Kind)
        {
          case "youtube#video":
            videos.Add(String.Format("{0} ({1})", searchResult.Snippet.Title, searchResult.Id.VideoId));
          break;

          case "youtube#channel":
            channels.Add(String.Format("{0} ({1})", searchResult.Snippet.Title, searchResult.Id.ChannelId));
          break;

          case "youtube#playlist":
            playlists.Add(String.Format("{0} ({1})", searchResult.Snippet.Title, searchResult.Id.PlaylistId));
          break;
        }
      }

      CommandLine.WriteLine(String.Format("Videos:\n{0}\n", String.Join("\n", videos.ToArray())));
      CommandLine.WriteLine(String.Format("Channels:\n{0}\n", String.Join("\n", channels.ToArray())));
      CommandLine.WriteLine(String.Format("Playlists:\n{0}\n", String.Join("\n", playlists.ToArray())));

      CommandLine.PressAnyKeyToExit();
    }
  }
}
