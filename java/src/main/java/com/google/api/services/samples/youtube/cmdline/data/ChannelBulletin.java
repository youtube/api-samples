/*
 * Copyright (c) 2012 Google Inc.
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
import com.google.api.services.youtube.model.*;
import com.google.common.collect.Lists;

import java.util.Calendar;
import java.util.List;

/**
 * Create a video bulletin that is posted to the user's channel feed.
 *
 * @author Jeremy Walker
 */
public class ChannelBulletin {

    /**
     * Define a global instance of a Youtube object, which will be used
     * to make YouTube Data API requests.
     */
    private static YouTube youtube;

    /*
     * Define a global instance of the video ID that will be posted as a
     * bulletin into the user's channel feed. In practice, you will probably
     * retrieve this value from a search or your app.
     */
    private static String VIDEO_ID = "L-oNKK1CrnU";


    /**
     * Authorize the user, call the youtube.channels.list method to retrieve
     * information about the user's YouTube channel, and post a bulletin with
     * a video ID to that channel.
     *
     * @param args command line args (not used).
     */
    public static void main(String[] args) {

        // This OAuth 2.0 access scope allows for full read/write access to the
        // authenticated user's account.
        List<String> scopes = Lists.newArrayList("https://www.googleapis.com/auth/youtube");

        try {
            // Authorize the request.
            Credential credential = Auth.authorize(scopes, "channelbulletin");

            // This object is used to make YouTube Data API requests.
            youtube = new YouTube.Builder(Auth.HTTP_TRANSPORT, Auth.JSON_FACTORY, credential).setApplicationName(
                    "youtube-cmdline-channelbulletin-sample").build();

            // Construct a request to retrieve the current user's channel ID.
            // See https://developers.google.com/youtube/v3/docs/channels/list
            YouTube.Channels.List channelRequest = youtube.channels().list("contentDetails");
            channelRequest.setMine(true);

            // In the API response, only include channel information needed
            // for this use case.
            channelRequest.setFields("items/contentDetails");
            ChannelListResponse channelResult = channelRequest.execute();

            List<Channel> channelsList = channelResult.getItems();

            if (channelsList != null) {
                // The user's default channel is the first item in the list.
                String channelId = channelsList.get(0).getId();

                // Create the snippet for the activity resource that
                // represents the channel bulletin. Set its channel ID
                // and description.
                ActivitySnippet snippet = new ActivitySnippet();
                snippet.setChannelId(channelId);
                Calendar cal = Calendar.getInstance();
                snippet.setDescription("Bulletin test video via YouTube API on " + cal.getTime());

                // Create a resourceId that identifies the video ID. You could
                // set the kind to "youtube#playlist" and use a playlist ID
                // instead of a video ID.
                ResourceId resource = new ResourceId();
                resource.setKind("youtube#video");
                resource.setVideoId(VIDEO_ID);

                ActivityContentDetailsBulletin bulletin = new ActivityContentDetailsBulletin();
                bulletin.setResourceId(resource);

                // Construct the ActivityContentDetails object for the request.
                ActivityContentDetails contentDetails = new ActivityContentDetails();
                contentDetails.setBulletin(bulletin);

                // Construct the resource, including the snippet and content
                // details, to send in the activities.insert
                Activity activity = new Activity();
                activity.setSnippet(snippet);
                activity.setContentDetails(contentDetails);

                // The API request identifies the resource parts that are being
                // written (contentDetails and snippet). The API response will
                // also include those parts.
                YouTube.Activities.Insert insertActivities =
                        youtube.activities().insert("contentDetails,snippet", activity);
                // Return the newly created activity resource.
                Activity newActivityInserted = insertActivities.execute();

                if (newActivityInserted != null) {
                    System.out.println(
                            "New Activity inserted of type " + newActivityInserted.getSnippet().getType());
                    System.out.println(" - Video id "
                            + newActivityInserted.getContentDetails().getBulletin().getResourceId().getVideoId());
                    System.out.println(
                            " - Description: " + newActivityInserted.getSnippet().getDescription());
                    System.out.println(" - Posted on " + newActivityInserted.getSnippet().getPublishedAt());
                } else {
                    System.out.println("Activity failed.");
                }

            } else {
                System.out.println("No channels are assigned to this user.");
            }
        } catch (GoogleJsonResponseException e) {
            e.printStackTrace();
            System.err.println("There was a service error: " + e.getDetails().getCode() + " : "
                    + e.getDetails().getMessage());

        } catch (Throwable t) {
            t.printStackTrace();
        }
    }
}
