/*
 * Copyright (c) 2013 Google Inc.
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
import com.google.api.services.samples.youtube.cmdline.Auth;
import com.google.api.services.youtube.YouTube;
import com.google.api.services.youtube.model.Video;
import com.google.api.services.youtube.model.VideoListResponse;
import com.google.api.services.youtube.model.VideoSnippet;
import com.google.common.collect.Lists;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.List;

/**
 * Update a video by adding a keyword tag to its metadata. The demo uses the
 * YouTube Data API (v3) and OAuth 2.0 for authorization.
 *
 * @author Ibrahim Ulukaya
 */
public class UpdateVideo {

    /**
     * Define a global instance of a Youtube object, which will be used
     * to make YouTube Data API requests.
     */
    private static YouTube youtube;

    /**
     * Add a keyword tag to a video that the user specifies. Use OAuth 2.0 to
     * authorize the API request.
     *
     * @param args command line args (not used).
     */
    public static void main(String[] args) {

        // This OAuth 2.0 access scope allows for full read/write access to the
        // authenticated user's account.
        List<String> scopes = Lists.newArrayList("https://www.googleapis.com/auth/youtube");

        try {
            // Authorize the request.
            Credential credential = Auth.authorize(scopes, "updatevideo");

            // This object is used to make YouTube Data API requests.
            youtube = new YouTube.Builder(Auth.HTTP_TRANSPORT, Auth.JSON_FACTORY, credential)
                    .setApplicationName("youtube-cmdline-updatevideo-sample").build();

            // Prompt the user to enter the video ID of the video being updated.
            String videoId = getVideoIdFromUser();
            System.out.println("You chose " + videoId + " to update.");

            // Prompt the user to enter a keyword tag to add to the video.
            String tag = getTagFromUser();
            System.out.println("You chose " + tag + " as a tag.");

            // Call the YouTube Data API's youtube.videos.list method to
            // retrieve the resource that represents the specified video.
            YouTube.Videos.List listVideosRequest = youtube.videos().list("snippet").setId(videoId);
            VideoListResponse listResponse = listVideosRequest.execute();

            // Since the API request specified a unique video ID, the API
            // response should return exactly one video. If the response does
            // not contain a video, then the specified video ID was not found.
            List<Video> videoList = listResponse.getItems();
            if (videoList.isEmpty()) {
                System.out.println("Can't find a video with ID: " + videoId);
                return;
            }

            // Extract the snippet from the video resource.
            Video video = videoList.get(0);
            VideoSnippet snippet = video.getSnippet();

            // Preserve any tags already associated with the video. If the
            // video does not have any tags, create a new array. Append the
            // provided tag to the list of tags associated with the video.
            List<String> tags = snippet.getTags();
            if (tags == null) {
                tags = new ArrayList<String>(1);
                snippet.setTags(tags);
            }
            tags.add(tag);

            // Update the video resource by calling the videos.update() method.
            YouTube.Videos.Update updateVideosRequest = youtube.videos().update("snippet", video);
            Video videoResponse = updateVideosRequest.execute();

            // Print information from the updated resource.
            System.out.println("\n================== Returned Video ==================\n");
            System.out.println("  - Title: " + videoResponse.getSnippet().getTitle());
            System.out.println("  - Tags: " + videoResponse.getSnippet().getTags());

        } catch (GoogleJsonResponseException e) {
            System.err.println("GoogleJsonResponseException code: " + e.getDetails().getCode() + " : "
                    + e.getDetails().getMessage());
            e.printStackTrace();
        } catch (IOException e) {
            System.err.println("IOException: " + e.getMessage());
            e.printStackTrace();
        } catch (Throwable t) {
            System.err.println("Throwable: " + t.getMessage());
            t.printStackTrace();
        }
    }

    /*
     * Prompt the user to enter a keyword tag.
     */
    private static String getTagFromUser() throws IOException {

        String keyword = "";

        System.out.print("Please enter a tag for your video: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        keyword = bReader.readLine();

        if (keyword.length() < 1) {
            // If the user doesn't enter a tag, use the default value "New Tag."
            keyword = "New Tag";
        }
        return keyword;
    }

    /*
     * Prompt the user to enter a video ID.
     */
    private static String getVideoIdFromUser() throws IOException {

        String videoId = "";

        System.out.print("Please enter a video Id to update: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        videoId = bReader.readLine();

        if (videoId.length() < 1) {
            // Exit if the user doesn't provide a value.
            System.out.print("Video Id can't be empty!");
            System.exit(1);
        }

        return videoId;
    }

}
