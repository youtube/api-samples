/*
 * Copyright (c) 2015 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under the License
 * is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
 * or implied. See the License for the specific language governing permissions and limitations under
 * the License.
 */

package com.google.api.services.samples.youtube.cmdline.data;

import com.google.api.client.auth.oauth2.Credential;
import com.google.api.client.googleapis.json.GoogleJsonResponseException;
import com.google.api.client.googleapis.media.MediaHttpDownloader;
import com.google.api.client.googleapis.media.MediaHttpDownloaderProgressListener;
import com.google.api.client.googleapis.media.MediaHttpUploader;
import com.google.api.client.googleapis.media.MediaHttpUploaderProgressListener;
import com.google.api.client.http.InputStreamContent;
import com.google.api.services.samples.youtube.cmdline.Auth;
import com.google.api.services.youtube.YouTube;
import com.google.api.services.youtube.YouTube.Captions.Download;
import com.google.api.services.youtube.YouTube.Captions.Insert;
import com.google.api.services.youtube.YouTube.Captions.Update;
import com.google.api.services.youtube.model.Caption;
import com.google.api.services.youtube.model.CaptionListResponse;
import com.google.api.services.youtube.model.CaptionSnippet;
import com.google.common.collect.Lists;

import java.io.BufferedInputStream;
import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.util.List;

/**
 * This sample creates and manages caption tracks by:
 *
 * 1. Uploading a caption track for a video via "captions.insert" method.
 * 2. Getting the caption tracks for a video via "captions.list" method.
 * 3. Updating an existing caption track via "captions.update" method.
 * 4. Download a caption track via "captions.download" method.
 * 5. Deleting an existing caption track via "captions.delete" method.
 *
 * @author Ibrahim Ulukaya
 */
public class Captions {

    /**
     * Define a global instance of a YouTube object, which will be used to make
     * YouTube Data API requests.
     */
    private static YouTube youtube;

    /**
     * Define a global variable that specifies the MIME type of the caption
     * being uploaded.
     */
    private static final String CAPTION_FILE_FORMAT = "*/*";

    /**
     * Define a global variable that specifies the caption download format.
     */
    private static final String SRT = "srt";


    /**
     * Upload, list, update, download, and delete caption tracks.
     *
     * @param args command line args (not used).
     */
    public static void main(String[] args) {

        // This OAuth 2.0 access scope allows for full read/write access to the
        // authenticated user's account and requires requests to use an SSL connection.
        List<String> scopes = Lists.newArrayList("https://www.googleapis.com/auth/youtube.force-ssl");

        try {
            // Authorize the request.
            Credential credential = Auth.authorize(scopes, "captions");

            // This object is used to make YouTube Data API requests.
            youtube = new YouTube.Builder(Auth.HTTP_TRANSPORT, Auth.JSON_FACTORY, credential)
                    .setApplicationName("youtube-cmdline-captions-sample").build();

            // Prompt the user to specify the action of the be achieved.
            String actionString = getActionFromUser();
            System.out.println("You chose " + actionString + ".");

            Action action = Action.valueOf(actionString.toUpperCase());
            switch (action) {
              case UPLOAD:
                uploadCaption(getVideoId(), getLanguage(), getName(), getCaptionFromUser());
                break;
              case LIST:
                listCaptions(getVideoId());
                break;
              case UPDATE:
                updateCaption(getCaptionIDFromUser(), getUpdateCaptionFromUser());
                break;
              case DOWNLOAD:
                downloadCaption(getCaptionIDFromUser());
                break;
              case DELETE:
                deleteCaption(getCaptionIDFromUser());
                break;
              default:
                // All the available methods are used in sequence just for the sake
                // of an example.

                //Prompt the user to specify a video to upload the caption track for and
                // a language, a name, a binary file for the caption track. Then upload the
                // caption track with the values that are selected by the user.
                String videoId = getVideoId();
                uploadCaption(videoId, getLanguage(), getName(), getCaptionFromUser());
                List<Caption> captions = listCaptions(videoId);
                if (captions.isEmpty()) {
                    System.out.println("Can't get video caption tracks.");
                } else {
                    // Retrieve the first uploaded caption track.
                    String firstCaptionId = captions.get(0).getId();

                    updateCaption(firstCaptionId, null);
                    downloadCaption(firstCaptionId);
                    deleteCaption(firstCaptionId);
                }
            }
        } catch (GoogleJsonResponseException e) {
            System.err.println("GoogleJsonResponseException code: " + e.getDetails().getCode()
                    + " : " + e.getDetails().getMessage());
            e.printStackTrace();

        } catch (IOException e) {
            System.err.println("IOException: " + e.getMessage());
            e.printStackTrace();
        } catch (Throwable t) {
            System.err.println("Throwable: " + t.getMessage());
            t.printStackTrace();
        }
    }

    /**
     * Deletes a caption track for a YouTube video. (captions.delete)
     *
     * @param captionId The id parameter specifies the caption ID for the resource
     * that is being deleted. In a caption resource, the id property specifies the
     * caption track's ID.
     * @throws IOException
     */
    private static void deleteCaption(String captionId) throws IOException {
      // Call the YouTube Data API's captions.delete method to
      // delete an existing caption track.
      youtube.captions().delete(captionId);
      System.out.println("  -  Deleted caption: " + captionId);
    }

    /**
     * Downloads a caption track for a YouTube video. (captions.download)
     *
     * @param captionId The id parameter specifies the caption ID for the resource
     * that is being downloaded. In a caption resource, the id property specifies the
     * caption track's ID.
     * @throws IOException
     */
    private static void downloadCaption(String captionId) throws IOException {
      // Create an API request to the YouTube Data API's captions.download
      // method to download an existing caption track.
      Download captionDownload = youtube.captions().download(captionId).setTfmt(SRT);

      // Set the download type and add an event listener.
      MediaHttpDownloader downloader = captionDownload.getMediaHttpDownloader();

      // Indicate whether direct media download is enabled. A value of
      // "True" indicates that direct media download is enabled and that
      // the entire media content will be downloaded in a single request.
      // A value of "False," which is the default, indicates that the
      // request will use the resumable media download protocol, which
      // supports the ability to resume a download operation after a
      // network interruption or other transmission failure, saving
      // time and bandwidth in the event of network failures.
      downloader.setDirectDownloadEnabled(false);

      // Set the download state for the caption track file.
      MediaHttpDownloaderProgressListener downloadProgressListener = new MediaHttpDownloaderProgressListener() {
          @Override
          public void progressChanged(MediaHttpDownloader downloader) throws IOException {
              switch (downloader.getDownloadState()) {
                  case MEDIA_IN_PROGRESS:
                      System.out.println("Download in progress");
                      System.out.println("Download percentage: " + downloader.getProgress());
                      break;
                  // This value is set after the entire media file has
                  //  been successfully downloaded.
                  case MEDIA_COMPLETE:
                      System.out.println("Download Completed!");
                      break;
                  // This value indicates that the download process has
                  //  not started yet.
                  case NOT_STARTED:
                      System.out.println("Download Not Started!");
                      break;
              }
          }
      };
      downloader.setProgressListener(downloadProgressListener);

      OutputStream outputFile = new FileOutputStream("captionFile.srt");
      // Download the caption track.
      captionDownload.executeAndDownloadTo(outputFile);
    }

    /**
     * Updates a caption track's draft status to publish it.
     * Updates the track with a new binary file as well if it is present.  (captions.update)
     *
     * @param captionId The id parameter specifies the caption ID for the resource
     * that is being updated. In a caption resource, the id property specifies the
     * caption track's ID.
     * @param captionFile caption track binary file.
     * @throws IOException
     */
    private static void updateCaption(String captionId, File captionFile) throws IOException {
      // Modify caption's isDraft property to unpublish a caption track.
      CaptionSnippet updateCaptionSnippet = new CaptionSnippet();
      updateCaptionSnippet.setIsDraft(false);
      Caption updateCaption = new Caption();
      updateCaption.setId(captionId);
      updateCaption.setSnippet(updateCaptionSnippet);
      
      Caption captionUpdateResponse;

      if (captionFile == null) {
        // Call the YouTube Data API's captions.update method to update an existing caption track.
        captionUpdateResponse = youtube.captions().update("snippet", updateCaption).execute();

      } else {
        // Create an object that contains the caption file's contents.
        InputStreamContent mediaContent = new InputStreamContent(
                CAPTION_FILE_FORMAT, new BufferedInputStream(new FileInputStream(captionFile)));
        mediaContent.setLength(captionFile.length());

        // Create an API request that specifies that the mediaContent
        // object is the caption of the specified video.
        Update captionUpdate = youtube.captions().update("snippet", updateCaption, mediaContent);

        // Set the upload type and add an event listener.
        MediaHttpUploader uploader = captionUpdate.getMediaHttpUploader();

        // Indicate whether direct media upload is enabled. A value of
        // "True" indicates that direct media upload is enabled and that
        // the entire media content will be uploaded in a single request.
        // A value of "False," which is the default, indicates that the
        // request will use the resumable media upload protocol, which
        // supports the ability to resume an upload operation after a
        // network interruption or other transmission failure, saving
        // time and bandwidth in the event of network failures.
        uploader.setDirectUploadEnabled(false);

        // Set the upload state for the caption track file.
        MediaHttpUploaderProgressListener progressListener = new MediaHttpUploaderProgressListener() {
            @Override
            public void progressChanged(MediaHttpUploader uploader) throws IOException {
                switch (uploader.getUploadState()) {
                    // This value is set before the initiation request is
                    // sent.
                    case INITIATION_STARTED:
                        System.out.println("Initiation Started");
                        break;
                    // This value is set after the initiation request
                    //  completes.
                    case INITIATION_COMPLETE:
                        System.out.println("Initiation Completed");
                        break;
                    // This value is set after a media file chunk is
                    // uploaded.
                    case MEDIA_IN_PROGRESS:
                        System.out.println("Upload in progress");
                        System.out.println("Upload percentage: " + uploader.getProgress());
                        break;
                    // This value is set after the entire media file has
                    //  been successfully uploaded.
                    case MEDIA_COMPLETE:
                        System.out.println("Upload Completed!");
                        break;
                    // This value indicates that the upload process has
                    //  not started yet.
                    case NOT_STARTED:
                        System.out.println("Upload Not Started!");
                        break;
                }
            }
        };
        uploader.setProgressListener(progressListener);

        // Upload the caption track.
        captionUpdateResponse = captionUpdate.execute();
        System.out.println("\n================== Uploaded New Caption Track ==================\n");
      }
      
      // Print information from the API response.
      System.out.println("\n================== Updated Caption Track ==================\n");
      CaptionSnippet snippet = captionUpdateResponse.getSnippet();
      System.out.println("  - ID: " + captionUpdateResponse.getId());
      System.out.println("  - Name: " + snippet.getName());
      System.out.println("  - Language: " + snippet.getLanguage());
      System.out.println("  - Draft Status: " + snippet.getIsDraft());
      System.out.println("\n-------------------------------------------------------------\n");
    }

    /**
     * Returns a list of caption tracks. (captions.listCaptions)
     *
     * @param videoId The videoId parameter instructs the API to return the
     * caption tracks for the video specified by the video id.
     * @throws IOException
     */
    private static List<Caption> listCaptions(String videoId) throws IOException {
      // Call the YouTube Data API's captions.list method to
      // retrieve video caption tracks.
      CaptionListResponse captionListResponse = youtube.captions().
          list("snippet", videoId).execute();

      List<Caption> captions = captionListResponse.getItems();
      // Print information from the API response.
      System.out.println("\n================== Returned Caption Tracks ==================\n");
      CaptionSnippet snippet;
      for (Caption caption : captions) {
          snippet = caption.getSnippet();
          System.out.println("  - ID: " + caption.getId());
          System.out.println("  - Name: " + snippet.getName());
          System.out.println("  - Language: " + snippet.getLanguage());
          System.out.println("\n-------------------------------------------------------------\n");
      }

      return captions;
    }

    /**
     * Uploads a caption track in draft status that matches the API request parameters.
     * (captions.insert)
     *
     * @param videoId the YouTube video ID of the video for which the API should
     *  return caption tracks.
     * @param captionLanguage language of the caption track.
     * @param captionName name of the caption track.
     * @param captionFile caption track binary file.
     * @throws IOException
     */
    private static void uploadCaption(String videoId, String captionLanguage,
        String captionName, File captionFile) throws IOException {
      // Add extra information to the caption before uploading.
      Caption captionObjectDefiningMetadata = new Caption();

      // Most of the caption's metadata is set on the CaptionSnippet object.
      CaptionSnippet snippet = new CaptionSnippet();

      // Set the video, language, name and draft status of the caption.
      snippet.setVideoId(videoId);
      snippet.setLanguage(captionLanguage);
      snippet.setName(captionName);
      snippet.setIsDraft(true);

      // Add the completed snippet object to the caption resource.
      captionObjectDefiningMetadata.setSnippet(snippet);

      // Create an object that contains the caption file's contents.
      InputStreamContent mediaContent = new InputStreamContent(
              CAPTION_FILE_FORMAT, new BufferedInputStream(new FileInputStream(captionFile)));
      mediaContent.setLength(captionFile.length());

      // Create an API request that specifies that the mediaContent
      // object is the caption of the specified video.
      Insert captionInsert = youtube.captions().insert("snippet", captionObjectDefiningMetadata, mediaContent);

      // Set the upload type and add an event listener.
      MediaHttpUploader uploader = captionInsert.getMediaHttpUploader();

      // Indicate whether direct media upload is enabled. A value of
      // "True" indicates that direct media upload is enabled and that
      // the entire media content will be uploaded in a single request.
      // A value of "False," which is the default, indicates that the
      // request will use the resumable media upload protocol, which
      // supports the ability to resume an upload operation after a
      // network interruption or other transmission failure, saving
      // time and bandwidth in the event of network failures.
      uploader.setDirectUploadEnabled(false);

      // Set the upload state for the caption track file.
      MediaHttpUploaderProgressListener progressListener = new MediaHttpUploaderProgressListener() {
          @Override
          public void progressChanged(MediaHttpUploader uploader) throws IOException {
              switch (uploader.getUploadState()) {
                  // This value is set before the initiation request is
                  // sent.
                  case INITIATION_STARTED:
                      System.out.println("Initiation Started");
                      break;
                  // This value is set after the initiation request
                  //  completes.
                  case INITIATION_COMPLETE:
                      System.out.println("Initiation Completed");
                      break;
                  // This value is set after a media file chunk is
                  // uploaded.
                  case MEDIA_IN_PROGRESS:
                      System.out.println("Upload in progress");
                      System.out.println("Upload percentage: " + uploader.getProgress());
                      break;
                  // This value is set after the entire media file has
                  //  been successfully uploaded.
                  case MEDIA_COMPLETE:
                      System.out.println("Upload Completed!");
                      break;
                  // This value indicates that the upload process has
                  //  not started yet.
                  case NOT_STARTED:
                      System.out.println("Upload Not Started!");
                      break;
              }
          }
      };
      uploader.setProgressListener(progressListener);

      // Upload the caption track.
      Caption uploadedCaption = captionInsert.execute();

      // Print the metadata of the uploaded caption track.
      System.out.println("\n================== Uploaded Caption Track ==================\n");
      snippet = uploadedCaption.getSnippet();
      System.out.println("  - ID: " + uploadedCaption.getId());
      System.out.println("  - Name: " + snippet.getName());
      System.out.println("  - Language: " + snippet.getLanguage());
      System.out.println("  - Status: " + snippet.getStatus());
      System.out
          .println("\n-------------------------------------------------------------\n");
    }

    /*
     * Prompt the user to enter a caption track ID. Then return the ID.
     */
    private static String getCaptionIDFromUser() throws IOException {

        String captionId = "";

        System.out.print("Please enter a caption track id: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        captionId = bReader.readLine();

        System.out.println("You chose " + captionId + ".");
        return captionId;
    }

    /*
     * Prompt the user to enter a video ID. Then return the ID.
     */
    private static String getVideoId() throws IOException {

        String videoId = "";

        System.out.print("Please enter a video id: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        videoId = bReader.readLine();

        System.out.println("You chose " + videoId + " for captions.");
        return videoId;
    }

    /*
     * Prompt the user to enter a name for the caption track. Then return the name.
     */
    private static String getName() throws IOException {

        String name = "";

        System.out.print("Please enter a caption track name: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        name = bReader.readLine();

        if (name.length() < 1) {
            // If nothing is entered, defaults to "YouTube For Developers".
            name = "YouTube for Developers";
        }

        System.out.println("You chose " + name + " as caption track name.");
        return name;
    }

    /*
     * Prompt the user to enter a language for the caption track. Then return the language.
     */
    private static String getLanguage() throws IOException {

        String language = "";

        System.out.print("Please enter the caption language: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        language = bReader.readLine();

        if (language.length() < 1) {
            // If nothing is entered, defaults to "en".
            language = "en";
        }

        System.out.println("You chose " + language + " as caption track language.");
        return language;
    }

    /*
     * Prompt the user to enter the path for the caption track file being uploaded.
     */
    private static File getCaptionFromUser() throws IOException {

        String path = "";

        System.out.print("Please enter the path of the caption track file to upload: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        path = bReader.readLine();

        if (path.length() < 1) {
            // Exit if the user does not provide a path to the file.
            System.out.print("Path can not be empty!");
            System.exit(1);
        }

        File captionFile = new File(path);
        System.out.println("You chose " + captionFile + " to upload.");

        return captionFile;
    }

    /*
     * Prompt the user to enter the path for the caption track file being replaced.
     */
    private static File getUpdateCaptionFromUser() throws IOException {

        String path = "";

        System.out.print("Please enter the path of the new caption track file to upload"
            + " (Leave empty if you don't want to upload a new file.):");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        path = bReader.readLine();

        if (path.length() < 1) {
            return null;
        }

        File captionFile = new File(path);
        System.out.println("You chose " + captionFile + " to upload.");

        return captionFile;
    }

    /*
     * Prompt the user to enter an action. Then return the action.
     */
    private static String getActionFromUser() throws IOException {

        String action = "";

        System.out.print("Please choose action to be accomplished: ");
        System.out.print("Options are: 'upload', 'list', 'update', 'download', 'delete',"
            + " and 'all' ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        action = bReader.readLine();

        return action;
    }

    public enum Action {
      UPLOAD,
      LIST,
      UPDATE,
      DOWNLOAD,
      DELETE,
      ALL
    }
}
