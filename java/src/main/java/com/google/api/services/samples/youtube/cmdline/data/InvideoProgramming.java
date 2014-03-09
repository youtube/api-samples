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
import com.google.api.client.http.InputStreamContent;
import com.google.api.services.samples.youtube.cmdline.Auth;
import com.google.api.services.youtube.YouTube;
import com.google.api.services.youtube.model.*;
import com.google.common.collect.Lists;

import java.io.IOException;
import java.math.BigInteger;
import java.util.List;

/**
 * Add a featured video to a channel.
 *
 * @author Ikai Lan <ikai@google.com>
 */
public class InvideoProgramming {

    /**
     * Define a global instance of a Youtube object, which will be used
     * to make YouTube Data API requests.
     */
    private static YouTube youtube;

    /**
     * This sample video introduces WebM on YouTube Developers Live.
     */
    private static final String FEATURED_VIDEO_ID = "w4eiUiauo2w";

    /**
     * This code sample demonstrates different ways that the API can be used to
     * promote your channel content. It includes code for the following tasks:
     * <ol>
     * <li>Feature a video.</li>
     * <li>Feature a link to a social media channel.</li>
     * <li>Set a watermark for videos on your channel.</li>
     * </ol>
     *
     * @param args command line args (not used).
     */
    public static void main(String[] args) {

        // This OAuth 2.0 access scope allows for full read/write access to the
        // authenticated user's account.
        List<String> scopes = Lists.newArrayList("https://www.googleapis.com/auth/youtube");

        try {
            // Authorize the request.
            Credential credential = Auth.authorize(scopes, "invideoprogramming");

            // This object is used to make YouTube Data API requests.
            youtube = new YouTube.Builder(Auth.HTTP_TRANSPORT, Auth.JSON_FACTORY, credential)
                    .setApplicationName("youtube-cmdline-invideoprogramming-sample")
                    .build();

            // Construct a request to retrieve the current user's channel ID.
            // In the API response, only include channel information needed for
            // this use case. The channel's uploads playlist identifies the
            // channel's most recently uploaded video.
            // See https://developers.google.com/youtube/v3/docs/channels/list
            ChannelListResponse channelListResponse = youtube.channels().list("id,contentDetails")
                    .setMine(true)
                    .setFields("items(contentDetails/relatedPlaylists/uploads,id)")
                    .execute();

            // The user's default channel is the first item in the list. If the
            // user does not have a channel, this code should throw a
            // GoogleJsonResponseException explaining the issue.
            Channel myChannel = channelListResponse.getItems().get(0);
            String channelId = myChannel.getId();

            // The promotion appears 15000ms (15 seconds) before the video ends.
            InvideoTiming invideoTiming = new InvideoTiming();
            invideoTiming.setOffsetMs(BigInteger.valueOf(15000l));
            invideoTiming.setType("offsetFromEnd");

            // This is one type of promotion and promotes a video.
            PromotedItemId promotedItemId = new PromotedItemId();
            promotedItemId.setType("video");
            promotedItemId.setVideoId(FEATURED_VIDEO_ID);

            // Set a custom message providing additional information about the
            // promoted video or your channel.
            PromotedItem promotedItem = new PromotedItem();
            promotedItem.setCustomMessage("Check out this video about WebM!");
            promotedItem.setId(promotedItemId);

            // Construct an object representing the invideo promotion data, and
            // add it to the channel.
            InvideoPromotion invideoPromotion = new InvideoPromotion();
            invideoPromotion.setDefaultTiming(invideoTiming);
            invideoPromotion.setItems(Lists.newArrayList(promotedItem));

            Channel channel = new Channel();
            channel.setId(channelId);
            channel.setInvideoPromotion(invideoPromotion);

            Channel updateChannelResponse = youtube.channels()
                    .update("invideoPromotion", channel)
                    .execute();

            // Print data from the API response.
            System.out.println("\n================== Updated Channel Information ==================\n");
            System.out.println("\t- Channel ID: " + updateChannelResponse.getId());

            InvideoPromotion promotions = updateChannelResponse.getInvideoPromotion();
            promotedItem = promotions.getItems().get(0); // We only care about the first item
            System.out.println("\t- Invideo promotion video ID: " + promotedItem
                    .getId()
                    .getVideoId());
            System.out.println("\t- Promotion message: " + promotedItem.getCustomMessage());

            // In-video programming can also be used to feature links to
            // associated websites, merchant sites, or social networking sites.
            // The code below overrides the promotional video set above by
            // featuring a link to the YouTube Developers Twitter feed.
            PromotedItemId promotedTwitterFeed = new PromotedItemId();
            promotedTwitterFeed.setType("website");
            promotedTwitterFeed.setWebsiteUrl("https://twitter.com/youtubedev");

            promotedItem = new PromotedItem();
            promotedItem.setCustomMessage("Follow us on Twitter!");
            promotedItem.setId(promotedTwitterFeed);

            invideoPromotion.setItems(Lists.newArrayList(promotedItem));
            channel.setInvideoPromotion(invideoPromotion);

            // Call the API to set the in-video promotion data.
            updateChannelResponse = youtube.channels()
                    .update("invideoPromotion", channel)
                    .execute();

            // Print data from the API response.
            System.out.println("\n================== Updated Channel Information ==================\n");
            System.out.println("\t- Channel ID: " + updateChannelResponse.getId());

            promotions = updateChannelResponse.getInvideoPromotion();
            promotedItem = promotions.getItems().get(0);
            System.out.println("\t- Invideo promotion URL: " + promotedItem
                    .getId()
                    .getWebsiteUrl());
            System.out.println("\t- Promotion message: " + promotedItem.getCustomMessage());

            // This example sets a custom watermark for the channel. The image
            // used is the watermark.jpg file in the "resources/" directory.
            InputStreamContent mediaContent = new InputStreamContent("image/jpeg",
                    InvideoProgramming.class.getResourceAsStream("/watermark.jpg"));

            // Indicate that the watermark should display during the last 15
            // seconds of the video.
            InvideoTiming watermarkTiming = new InvideoTiming();
            watermarkTiming.setType("offsetFromEnd");
            watermarkTiming.setDurationMs(BigInteger.valueOf(15000l));
            watermarkTiming.setOffsetMs(BigInteger.valueOf(15000l));

            InvideoBranding invideoBranding = new InvideoBranding();
            invideoBranding.setTiming(watermarkTiming);
            youtube.watermarks().set(channelId, invideoBranding, mediaContent).execute();

        } catch (GoogleJsonResponseException e) {
            System.err.println("GoogleJsonResponseException code: " + e.getDetails().getCode() + " : "
                    + e.getDetails().getMessage());
            e.printStackTrace();

        } catch (IOException e) {
            System.err.println("IOException: " + e.getMessage());
            e.printStackTrace();
        }
    }

}
