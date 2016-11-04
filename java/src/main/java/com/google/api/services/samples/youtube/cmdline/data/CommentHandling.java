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
import com.google.api.services.youtube.model.CommentListResponse;
import com.google.api.services.youtube.model.CommentThreadListResponse;
import com.google.common.collect.Lists;

/**
 * This sample creates and manages comments by:
 *
 * 1. Retrieving the top-level comments for a video via "commentThreads.list" method.
 * 2. Replying to a comment thread via "comments.insert" method.
 * 3. Retrieving comment replies via "comments.list" method.
 * 4. Updating an existing comment via "comments.update" method.
 * 5. Sets moderation status of an existing comment via "comments.setModerationStatus" method.
 * 6. Marking a comment as spam via "comments.markAsSpam" method.
 * 7. Deleting an existing comment via "comments.delete" method.
 *
 * @author Ibrahim Ulukaya
 */
public class CommentHandling {

    /**
     * Define a global instance of a YouTube object, which will be used to make
     * YouTube Data API requests.
     */
    private static YouTube youtube;

    /**
     * List, reply to comment threads; list, update, moderate, mark and delete
     * replies.
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

            // Prompt the user for the ID of a video to comment on.
            // Retrieve the video ID that the user is commenting to.
            String videoId = getVideoId();
            System.out.println("You chose " + videoId + " to subscribe.");

            // Prompt the user for the comment text.
            // Retrieve the text that the user is commenting.
            String text = getText();
            System.out.println("You chose " + text + " to subscribe.");

            // All the available methods are used in sequence just for the sake
            // of an example.

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
                    CommentSnippet snippet = videoComment.getSnippet().getTopLevelComment()
                            .getSnippet();
                    System.out.println("  - Author: " + snippet.getAuthorDisplayName());
                    System.out.println("  - Comment: " + snippet.getTextDisplay());
                    System.out
                            .println("\n-------------------------------------------------------------\n");
                }
                CommentThread firstComment = videoComments.get(0);

                // Will use this thread as parent to new reply.
                String parentId = firstComment.getId();

                // Create a comment snippet with text.
                CommentSnippet commentSnippet = new CommentSnippet();
                commentSnippet.setTextOriginal(text);
                commentSnippet.setParentId(parentId);

                // Create a comment with snippet.
                Comment comment = new Comment();
                comment.setSnippet(commentSnippet);

                // Call the YouTube Data API's comments.insert method to reply
                // to a comment.
                // (If the intention is to create a new top-level comment,
                // commentThreads.insert
                // method should be used instead.)
                Comment commentInsertResponse = youtube.comments().insert("snippet", comment)
                        .execute();

                // Print information from the API response.
                System.out
                        .println("\n================== Created Comment Reply ==================\n");
                CommentSnippet snippet = commentInsertResponse.getSnippet();
                System.out.println("  - Author: " + snippet.getAuthorDisplayName());
                System.out.println("  - Comment: " + snippet.getTextDisplay());
                System.out
                        .println("\n-------------------------------------------------------------\n");

                // Call the YouTube Data API's comments.list method to retrieve
                // existing comment
                // replies.
                CommentListResponse commentsListResponse = youtube.comments().list("snippet")
                        .setParentId(parentId).setTextFormat("plainText").execute();
                List<Comment> comments = commentsListResponse.getItems();

                if (comments.isEmpty()) {
                    System.out.println("Can't get comment replies.");
                } else {
                    // Print information from the API response.
                    System.out
                            .println("\n================== Returned Comment Replies ==================\n");
                    for (Comment commentReply : comments) {
                        snippet = commentReply.getSnippet();
                        System.out.println("  - Author: " + snippet.getAuthorDisplayName());
                        System.out.println("  - Comment: " + snippet.getTextDisplay());
                        System.out
                                .println("\n-------------------------------------------------------------\n");
                    }
                    Comment firstCommentReply = comments.get(0);
                    firstCommentReply.getSnippet().setTextOriginal("updated");
                    Comment commentUpdateResponse = youtube.comments()
                            .update("snippet", firstCommentReply).execute();
                    // Print information from the API response.
                    System.out
                            .println("\n================== Updated Video Comment ==================\n");
                    snippet = commentUpdateResponse.getSnippet();
                    System.out.println("  - Author: " + snippet.getAuthorDisplayName());
                    System.out.println("  - Comment: " + snippet.getTextDisplay());
                    System.out
                            .println("\n-------------------------------------------------------------\n");

                    // Call the YouTube Data API's comments.setModerationStatus
                    // method to set moderation
                    // status of an existing comment.
                    youtube.comments().setModerationStatus(firstCommentReply.getId(), "published");
                    System.out.println("  -  Changed comment status to published: "
                            + firstCommentReply.getId());

                    // Call the YouTube Data API's comments.markAsSpam method to
                    // mark an existing comment as spam.
                    youtube.comments().markAsSpam(firstCommentReply.getId());
                    System.out.println("  -  Marked comment as spam: " + firstCommentReply.getId());

                    // Call the YouTube Data API's comments.delete method to
                    // delete an existing comment.
                    youtube.comments().delete(firstCommentReply.getId());
                    System.out
                            .println("  -  Deleted comment as spam: " + firstCommentReply.getId());
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
