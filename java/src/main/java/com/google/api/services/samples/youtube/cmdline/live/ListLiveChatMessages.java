/*
 * Copyright (c) 2017 Google Inc.
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
import com.google.api.services.youtube.YouTubeScopes;
import com.google.api.services.youtube.model.LiveChatMessage;
import com.google.api.services.youtube.model.LiveChatMessageAuthorDetails;
import com.google.api.services.youtube.model.LiveChatMessageListResponse;
import com.google.api.services.youtube.model.LiveChatMessageSnippet;
import com.google.api.services.youtube.model.LiveChatSuperChatDetails;
import com.google.common.collect.Lists;
import java.io.IOException;
import java.util.List;
import java.util.Timer;
import java.util.TimerTask;

/**
 * Lists live chat messages and SuperChat details from a live broadcast.
 *
 * The videoId is often included in the video's url, e.g.:
 * https://www.youtube.com/watch?v=L5Xc93_ZL60
 *                                 ^ videoId
 * The video URL may be found in the browser address bar, or by right-clicking a video and selecting
 * Copy video URL from the context menu.
 *
 * @author Jim Rogers
 */
public class ListLiveChatMessages {

    /**
     * Common fields to retrieve for chat messages
     */
    private static final String LIVE_CHAT_FIELDS =
        "items(authorDetails(channelId,displayName,isChatModerator,isChatOwner,isChatSponsor,"
            + "profileImageUrl),snippet(displayMessage,superChatDetails,publishedAt)),"
            + "nextPageToken,pollingIntervalMillis";

    /**
     * Define a global instance of a Youtube object, which will be used
     * to make YouTube Data API requests.
     */
    private static YouTube youtube;

    /**
     * A timer used to schedule message retrieval.
     */
    private static Timer pollTimer;

    /**
     * Lists live chat messages and SuperChat details from a live broadcast.
     *
     * @param args videoId (optional). If the videoId is given, live chat messages will be retrieved
     * from the chat associated with this video. If the videoId is not specified, the signed in
     * user's current live broadcast will be used instead.
     */
    public static void main(String[] args) {

        // This OAuth 2.0 access scope allows for read-only access to the
        // authenticated user's account, but not other types of account access.
        List<String> scopes = Lists.newArrayList(YouTubeScopes.YOUTUBE_READONLY);

        try {
            // Authorize the request.
            Credential credential = Auth.authorize(scopes, "listlivechatmessages");

            // This object is used to make YouTube Data API requests.
            youtube = new YouTube.Builder(Auth.HTTP_TRANSPORT, Auth.JSON_FACTORY, credential)
                .setApplicationName("youtube-cmdline-listchatmessages-sample").build();

            // Get the liveChatId
            String liveChatId = GetLiveChatId.getLiveChatId(
                youtube,
                args.length == 1 ? args[0] : null);
            if (liveChatId != null) {
                System.out.println("Live chat id: " + liveChatId);
            } else {
                System.err.println("Unable to find a live chat id");
                System.exit(1);
            }

            /**
             * List live chat messages with poll interval from server. Alternatively, messages
             * may be requested at a fixed interval with listChatMessagesFixedPeriod, e.g.
             * listChatMessagesFixedPeriod(liveChatId, 1000, 0)
             */
            listChatMessages(liveChatId, null, 0);
        } catch (GoogleJsonResponseException e) {
            System.err
                .println("GoogleJsonResponseException code: " + e.getDetails().getCode() + " : "
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

    /**
     * Lists live chat messages, polling at the server supplied interval.
     *
     * @param liveChatId The live chat id to list messages from.
     * @param nextPageToken The page token from the previous request, if any.
     * @param delayMs The delay in milliseconds before making the request.
     */
    private static void listChatMessages(
        final String liveChatId,
        final String nextPageToken,
        long delayMs) {
        System.out.println(
            String.format("Getting chat messages in %1$.3f seconds...", delayMs * 0.001));
        pollTimer = new Timer();
        pollTimer.schedule(
            new TimerTask() {
                @Override
                public void run() {
                    try {
                        // Get chat messages from YouTube
                        LiveChatMessageListResponse response = youtube
                            .liveChatMessages()
                            .list(liveChatId, "snippet, authorDetails")
                            .setPageToken(nextPageToken)
                            .setFields(LIVE_CHAT_FIELDS)
                            .execute();

                        // Display messages and super chat details
                        List<LiveChatMessage> messages = response.getItems();
                        for (int i = 0; i < messages.size(); i++) {
                            LiveChatMessage message = messages.get(i);
                            LiveChatMessageSnippet snippet = message.getSnippet();
                            System.out.println(buildOutput(
                                snippet.getDisplayMessage(),
                                message.getAuthorDetails(),
                                snippet.getSuperChatDetails()));
                        }

                        // Request the next page of messages
                        listChatMessages(
                            liveChatId,
                            response.getNextPageToken(),
                            response.getPollingIntervalMillis());
                    } catch (Throwable t) {
                        System.err.println("Throwable: " + t.getMessage());
                        t.printStackTrace();
                    }
                }
            }, delayMs);
    }

    /**
     * Lists live chat messages, polling at the client supplied interval. This method is not
     * recommended because it will consume more API usage, but it may be necessary in some
     * applications that require lower latency. Page tokens do not work when polling faster than the
     * server supplied interval, so we need to keep track of the publish time to avoid duplicate
     * message output. Message ids will not work for tracking the last received message because
     * messages may be removed from chat.
     *
     * @param liveChatId The live chat id to list messages from.
     * @param periodMs The fixed interval to poll messages.
     * @param minPublishTime The minimum message time to output.
     */
    private static void listChatMessagesFixedPeriod(
        final String liveChatId,
        final long periodMs,
        final long minPublishTime) {
        System.out.println(
            String.format("Getting chat messages in %1$.3f seconds...", periodMs * 0.001));
        pollTimer = new Timer();
        pollTimer.schedule(
            new TimerTask() {
                @Override
                public void run() {
                    try {
                        // Get chat messages from YouTube
                        LiveChatMessageListResponse response = youtube
                            .liveChatMessages()
                            .list(liveChatId, "snippet, authorDetails")
                            .setFields(LIVE_CHAT_FIELDS)
                            .execute();

                        // Display messages and super chat details
                        long maxPublishTime = minPublishTime;
                        List<LiveChatMessage> messages = response.getItems();
                        for (int i = 0; i < messages.size(); i++) {
                            LiveChatMessage message = messages.get(i);
                            LiveChatMessageSnippet snippet = message.getSnippet();
                            long publishTime = snippet.getPublishedAt().getValue();
                            if (publishTime >= minPublishTime) {
                                System.out.println(buildOutput(
                                    snippet.getDisplayMessage(),
                                    message.getAuthorDetails(),
                                    snippet.getSuperChatDetails()));
                            }
                            maxPublishTime = Math.max(maxPublishTime, publishTime);
                        }

                        // Request the next page of messages
                        listChatMessagesFixedPeriod(liveChatId, periodMs, maxPublishTime + 1);
                    } catch (Throwable t) {
                        System.err.println("Throwable: " + t.getMessage());
                        t.printStackTrace();
                    }
                }
            }, periodMs);
    }

    /**
     * Formats a chat message for console output.
     *
     * @param message The display message to output.
     * @param author The author of the message.
     * @param superChatDetails SuperChat details associated with the message.
     * @return A formatted string for console output.
     */
    private static String buildOutput(
        String message,
        LiveChatMessageAuthorDetails author,
        LiveChatSuperChatDetails superChatDetails) {
        StringBuilder output = new StringBuilder();
        if (superChatDetails != null) {
            output.append(superChatDetails.getAmountDisplayString());
            output.append("SUPERCHAT RECEIVED FROM ");
        }
        output.append(author.getDisplayName());
        if (author.getIsChatOwner() || author.getIsChatOwner() || author.getIsChatSponsor()) {
            output.append(" (");
            boolean appendComma = false;
            if (author.getIsChatOwner()) {
                output.append("OWNER");
                appendComma = true;
            }
            if (author.getIsChatModerator()) {
                if (appendComma) {
                    output.append(", ");
                }
                output.append("MODERATOR");
                appendComma = true;
            }
            if (author.getIsChatSponsor()) {
                if (appendComma) {
                    output.append(", ");
                }
                output.append("SPONSER");
            }
            output.append(")");
        }
        if (message != null && !message.isEmpty()) {
            output.append(": ");
            output.append(message);
        }
        return output.toString();
    }
}
