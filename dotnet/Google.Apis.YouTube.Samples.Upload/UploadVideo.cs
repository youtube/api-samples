/*
 * Copyright 2015 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *
 */

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
  /// YouTube Data API v3 sample: upload a video.
  /// Relies on the Google APIs Client Library for .NET, v1.7.0 or higher.
  /// See https://code.google.com/p/google-api-dotnet-client/wiki/GettingStarted
  /// </summary>
  internal class UploadVideo
  {
    [STAThread]
    static void Main(string[] args)
    {
      Console.WriteLine("YouTube Data API: Upload Video");
      Console.WriteLine("==============================");

      try
      {
        new UploadVideo().Run().Wait();
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
            // This OAuth 2.0 access scope allows an application to upload files to the
            // authenticated user's YouTube channel, but doesn't allow other types of access.
            new[] { YouTubeService.Scope.YoutubeUpload },
            "user",
            CancellationToken.None
        );
      }


      var youtubeService = new YouTubeService(new BaseClientService.Initializer()
      {
        HttpClientInitializer = credential,
        ApplicationName = Assembly.GetExecutingAssembly().GetName().Name
      });

      var video = new Video();
      video.Snippet = new VideoSnippet();
      video.Snippet.Title = "Default Video Title";
      video.Snippet.Description = "Default Video Description";
      video.Snippet.Tags = new string[] { "tag1", "tag2" };
      video.Snippet.CategoryId = "22"; // See https://developers.google.com/youtube/v3/docs/videoCategories/list
      video.Status = new VideoStatus();
      video.Status.PrivacyStatus = "unlisted"; // or "private" or "public"
      var filePath = @"REPLACE_ME.mp4"; // Replace with path to actual movie file.

      using (var fileStream = new FileStream(filePath, FileMode.Open))
      {
        var videosInsertRequest = youtubeService.Videos.Insert(video, "snippet,status", fileStream, "video/*");
        videosInsertRequest.ProgressChanged += videosInsertRequest_ProgressChanged;
        videosInsertRequest.ResponseReceived += videosInsertRequest_ResponseReceived;

        await videosInsertRequest.UploadAsync();
      }
    }

    void videosInsertRequest_ProgressChanged(Google.Apis.Upload.IUploadProgress progress)
    {
      switch (progress.Status)
      {
        case UploadStatus.Uploading:
          Console.WriteLine("{0} bytes sent.", progress.BytesSent);
          break;

        case UploadStatus.Failed:
          Console.WriteLine("An error prevented the upload from completing.\n{0}", progress.Exception);
          break;
      }
    }

    void videosInsertRequest_ResponseReceived(Video video)
    {
      Console.WriteLine("Video id '{0}' was successfully uploaded.", video.Id);
    }
  }
}
