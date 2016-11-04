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

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.List;

import com.google.api.client.auth.oauth2.Credential;
import com.google.api.client.googleapis.json.GoogleJsonResponseException;
import com.google.api.services.samples.youtube.cmdline.Auth;
import com.google.api.services.youtube.YouTube;
import com.google.api.services.youtube.model.Comment;
import com.google.api.services.youtube.model.CommentSnippet;
import com.google.api.services.youtube.model.CommentThread;
import com.google.api.services.youtube.model.CommentThreadSnippet;
import com.google.api.services.youtube.model.CommentThreadListResponse;
import com.google.common.collect.Lists;

/**
 * This sample creates and manages top-level comments by:
 *
 * 1. Creating a top-level comments for a video and a channel via "commentThreads.insert" method.
 * 2. Retrieving the top-level comments for a video and a channel via "commentThreads.list" method.
 * 3. Updating an existing comments via "commentThreads.update" method.
 *
 * @author Ibrahim Ulukaya
 */
public class CommentThreads {

    /**
     * Define a global instance of a YouTube object, which will be used to make
     * YouTube Data API requests.
     */
    private static YouTube youtube;

    /**
     * Create, list and update top-level channel and video comments.
     *
     * @param args command line args (not used).
     */
    public static void main(String[] args) {

        // This OAuth 2.0 access scope allows for full read/write access to the
        // authenticated user's account and requires requests to use an SSL connection.
        List<String> scopes = Lists.newArrayList("https://www.googleapis.com/auth/youtube.force-ssl");

        try {
            // Authorize the request.
            Credential credential = Auth.authorize(scopes, "commentthreads");

            // This object is used to make YouTube Data API requests.
            youtube = new YouTube.Builder(Auth.HTTP_TRANSPORT, Auth.JSON_FACTORY, credential)
                    .setApplicationName("youtube-cmdline-commentthreads-sample").build();

            // Prompt the user for the ID of a channel to comment on.
            // Retrieve the channel ID that the user is commenting to.
            String channelId = getChannelId();
            System.out.println("You chose " + channelId + " to subscribe.");

            // Prompt the user for the ID of a video to comment on.
            // Retrieve the video ID that the user is commenting to.
            String videoId = getVideoId();
            System.out.println("You chose " + videoId + " to subscribe.");

            // Prompt the user for the comment text.
            // Retrieve the text that the user is commenting.
            String text = getText();
            System.out.println("You chose " + text + " to subscribe.");


            // Insert channel comment by omitting videoId.
            // Create a comment snippet with text.
            CommentSnippet commentSnippet = new CommentSnippet();
            commentSnippet.setTextOriginal(text);

            // Create a top-level comment with snippet.
            Comment topLevelComment = new Comment();
            topLevelComment.setSnippet(commentSnippet);

            // Create a comment thread snippet with channelId and top-level
            // comment.
            CommentThreadSnippet commentThreadSnippet = new CommentThreadSnippet();
            commentThreadSnippet.setChannelId(channelId);
            commentThreadSnippet.setTopLevelComment(topLevelComment);

            // Create a comment thread with snippet.
            CommentThread commentThread = new CommentThread();
            commentThread.setSnippet(commentThreadSnippet);

            // Call the YouTube Data API's commentThreads.insert method to
            // create a comment.
            CommentThread channelCommentInsertResponse = youtube.commentThreads()
                    .insert("snippet", commentThread).execute();
            // Print information from the API response.
            System.out
                    .println("\n================== Created Channel Comment ==================\n");
            CommentSnippet snippet = channelCommentInsertResponse.getSnippet().getTopLevelComment()
                    .getSnippet();
            System.out.println("  - Author: " + snippet.getAuthorDisplayName());
            System.out.println("  - Comment: " + snippet.getTextDisplay());
            System.out
                    .println("\n-------------------------------------------------------------\n");


            // Insert video comment
            commentThreadSnippet.setVideoId(videoId);
            // Call the YouTube Data API's commentThreads.insert method to
            // create a comment.
            CommentThread videoCommentInsertResponse = youtube.commentThreads()
                    .insert("snippet", commentThread).execute();
            // Print information from the API response.
            System.out
                    .println("\n================== Created Video Comment ==================\n");
            snippet = videoCommentInsertResponse.getSnippet().getTopLevelComment()
                    .getSnippet();
            System.out.println("  - Author: " + snippet.getAuthorDisplayName());
            System.out.println("  - Comment: " + snippet.getTextDisplay());
            System.out
                    .println("\n-------------------------------------------------------------\n");


            // Call the YouTube Data API's commentThreads.list method to
            // retrieve video comment threads.
            CommentThreadListResponse videoCommentsListResponse = youtube.commentThreads()
                    .list("snippet").setVideoId(videoId).setTextFormat("plainText").execute();
            List<CommentThread> videoComments = videoCommentsListResponse.getItems();

            if (videoComments.isEmpty()) {
                System.out.println("Can't get video comments.");
            } else {
                // Print information from the API response.
                System.out
                        .println("\n================== Returned Video Comments ==================\n");
                for (CommentThread videoComment : videoComments) {
                    snippet = videoComment.getSnippet().getTopLevelComment()
                            .getSnippet();
                    System.out.println("  - Author: " + snippet.getAuthorDisplayName());
                    System.out.println("  - Comment: " + snippet.getTextDisplay());
                    System.out
                            .println("\n-------------------------------------------------------------\n");
                }
                CommentThread firstComment = videoComments.get(0);
                firstComment.getSnippet().getTopLevelComment().getSnippet()
                        .setTextOriginal("updated");
                CommentThread videoCommentUpdateResponse = youtube.commentThreads()
                        .update("snippet", firstComment).execute();
                // Print information from the API response.
                System.out
                        .println("\n================== Updated Video Comment ==================\n");
                snippet = videoCommentUpdateResponse.getSnippet().getTopLevelComment()
                        .getSnippet();
                System.out.println("  - Author: " + snippet.getAuthorDisplayName());
                System.out.println("  - Comment: " + snippet.getTextDisplay());
                System.out
                        .println("\n-------------------------------------------------------------\n");

            }

            // Call the YouTube Data API's commentThreads.list method to
            // retrieve channel comment threads.
            CommentThreadListResponse channelCommentsListResponse = youtube.commentThreads()
                    .list("snippet").setChannelId(channelId).setTextFormat("plainText").execute();
            List<CommentThread> channelComments = channelCommentsListResponse.getItems();

            if (channelComments.isEmpty()) {
                System.out.println("Can't get channel comments.");
            } else {
                // Print information from the API response.
                System.out
                        .println("\n================== Returned Channel Comments ==================\n");
                for (CommentThread channelComment : channelComments) {
                    snippet = channelComment.getSnippet().getTopLevelComment()
                            .getSnippet();
                    System.out.println("  - Author: " + snippet.getAuthorDisplayName());
                    System.out.println("  - Comment: " + snippet.getTextDisplay());
                    System.out
                            .println("\n-------------------------------------------------------------\n");
                }
                CommentThread firstComment = channelComments.get(0);
                firstComment.getSnippet().getTopLevelComment().getSnippet()
                        .setTextOriginal("updated");
                CommentThread channelCommentUpdateResponse = youtube.commentThreads()
                        .update("snippet", firstComment).execute();
                // Print information from the API response.
                System.out
                        .println("\n================== Updated Channel Comment ==================\n");
                snippet = channelCommentUpdateResponse.getSnippet().getTopLevelComment()
                        .getSnippet();
                System.out.println("  - Author: " + snippet.getAuthorDisplayName());
                System.out.println("  - Comment: " + snippet.getTextDisplay());
                System.out
                        .println("\n-------------------------------------------------------------\n");

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

    /*
     * Prompt the user to enter a channel ID. Then return the ID.
     */
    private static String getChannelId() throws IOException {

        String channelId = "";

        System.out.print("Please enter a channel id: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        channelId = bReader.readLine();

        return channelId;
    }

    /*
     * Prompt the user to enter a video ID. Then return the ID.
     */
    private static String getVideoId() throws IOException {

        String videoId = "";

        System.out.print("Please enter a video id: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        videoId = bReader.readLine();

        return videoId;
    }

    /*
     * Prompt the user to enter text for a comment. Then return the text.
     */
    private static String getText() throws IOException {

        String text = "";

        System.out.print("Please enter a comment text: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        text = bReader.readLine();

        if (text.length() < 1) {
            // If nothing is entered, defaults to "YouTube For Developers."
            text = "YouTube For Developers.";
        }
        return text;
    }
}
