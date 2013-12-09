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

package com.google.api.services.samples.youtube.cmdline.live;

import com.google.api.client.auth.oauth2.Credential;
import com.google.api.client.googleapis.json.GoogleJsonResponseException;
import com.google.api.services.samples.youtube.cmdline.Auth;
import com.google.api.services.youtube.YouTube;
import com.google.api.services.youtube.model.LiveStream;
import com.google.api.services.youtube.model.LiveStreamListResponse;
import com.google.common.collect.Lists;

import java.io.IOException;
import java.util.List;

/**
 * Retrieve a list of a channel's streams, using OAuth 2.0 to authorize
 * API requests.
 *
 * @author Ibrahim Ulukaya
 */
public class ListStreams {

    /**
     * Define a global instance of a Youtube object, which will be used
     * to make YouTube Data API requests.
     */
    private static YouTube youtube;

    /**
     * List streams for the user's channel.
     */
    public static void main(String[] args) {

        // This OAuth 2.0 access scope allows for read-only access to the
        // authenticated user's account, but not other types of account access.
        List<String> scopes = Lists.newArrayList("https://www.googleapis.com/auth/youtube.readonly");

        try {
            // Authorize the request.
            Credential credential = Auth.authorize(scopes, "liststreams");

            // This object is used to make YouTube Data API requests.
            youtube = new YouTube.Builder(Auth.HTTP_TRANSPORT, Auth.JSON_FACTORY, credential)
                    .setApplicationName("youtube-cmdline-liststreams-sample")
                    .build();

            // Create a request to list liveStream resources.
            YouTube.LiveStreams.List livestreamRequest = youtube.liveStreams().list("id,snippet");

            // Modify results to only return the user's streams.
            livestreamRequest.setMine(true);

            // Execute the API request and return the list of streams.
            LiveStreamListResponse returnedListResponse = livestreamRequest.execute();
            List<LiveStream> returnedList = returnedListResponse.getItems();

            // Print information from the API response.
            System.out.println("\n================== Returned Streams ==================\n");
            for (LiveStream stream : returnedList) {
                System.out.println("  - Id: " + stream.getId());
                System.out.println("  - Title: " + stream.getSnippet().getTitle());
                System.out.println("  - Description: " + stream.getSnippet().getDescription());
                System.out.println("  - Published At: " + stream.getSnippet().getPublishedAt());
                System.out.println("\n-------------------------------------------------------------\n");
            }

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
}
