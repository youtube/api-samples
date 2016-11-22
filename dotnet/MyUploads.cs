using System;
using System.IO;
using System.Reflection;
using System.Threading;
using System.Threading.Tasks;

using Google.Apis.Auth.OAuth2;
using Google.Apis.Services;
using Google.Apis.Upload;
using Google.Apis.Util.Store;
using Google.Apis.YouTube.v3;
using Google.Apis.YouTube.v3.Data;

namespace Google.Apis.YouTube.Samples
{
  /// <summary>
  /// YouTube Data API v3 sample: retrieve my uploads.
  /// Relies on the Google APIs Client Library for .NET, v1.7.0 or higher.
  /// See https://developers.google.com/api-client-library/dotnet/get_started
  /// </summary>
  internal class MyUploads
  {
    [STAThread]
    static void Main(string[] args)
    {
      Console.WriteLine("YouTube Data API: My Uploads");
      Console.WriteLine("============================");

      try
      {
        new MyUploads().Run().Wait();
      }
      catch (AggregateException ex)
      {
        foreach (var e in ex.InnerExceptions)
        {
          Console.WriteLine("Error: " + e.Message);
        }
      }

      Console.WriteLine("Press any key to continue...");
      Console.ReadKey();
    }

    private async Task Run()
    {
      UserCredential credential;
      using (var stream = new FileStream("client_secrets.json", FileMode.Open, FileAccess.Read))
      {
        credential = await GoogleWebAuthorizationBroker.AuthorizeAsync(
            GoogleClientSecrets.Load(stream).Secrets,
            // This OAuth 2.0 access scope allows for read-only access to the authenticated 
            // user's account, but not other types of account access.
            new[] { YouTubeService.Scope.YoutubeReadonly },
            "user",
            CancellationToken.None,
            new FileDataStore(this.GetType().ToString())
        );
      }

      var youtubeService = new YouTubeService(new BaseClientService.Initializer()
      {
        HttpClientInitializer = credential,
        ApplicationName = this.GetType().ToString()
      });

      var channelsListRequest = youtubeService.Channels.List("contentDetails");
      channelsListRequest.Mine = true;

      // Retrieve the contentDetails part of the channel resource for the authenticated user's channel.
      var channelsListResponse = await channelsListRequest.ExecuteAsync();

      foreach (var channel in channelsListResponse.Items)
      {
        // From the API response, extract the playlist ID that identifies the list
        // of videos uploaded to the authenticated user's channel.
        var uploadsListId = channel.ContentDetails.RelatedPlaylists.Uploads;

        Console.WriteLine("Videos in list {0}", uploadsListId);

        var nextPageToken = "";
        while (nextPageToken != null)
        {
          var playlistItemsListRequest = youtubeService.PlaylistItems.List("snippet");
          playlistItemsListRequest.PlaylistId = uploadsListId;
          playlistItemsListRequest.MaxResults = 50;
          playlistItemsListRequest.PageToken = nextPageToken;

          // Retrieve the list of videos uploaded to the authenticated user's channel.
          var playlistItemsListResponse = await playlistItemsListRequest.ExecuteAsync();

          foreach (var playlistItem in playlistItemsListResponse.Items)
          {
            // Print information about each video.
            Console.WriteLine("{0} ({1})", playlistItem.Snippet.Title, playlistItem.Snippet.ResourceId.VideoId);
          }

          nextPageToken = playlistItemsListResponse.NextPageToken;
        }
      }
    }
  }
}
