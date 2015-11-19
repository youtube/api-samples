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
import com.google.api.client.util.ArrayMap;
import com.google.api.services.samples.youtube.cmdline.Auth;
import com.google.api.services.youtube.YouTube;
import com.google.api.services.youtube.model.Playlist;
import com.google.api.services.youtube.model.PlaylistListResponse;
import com.google.api.services.youtube.model.PlaylistLocalization;
import com.google.common.collect.Lists;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.List;
import java.util.Map;

/**
 * This sample sets and retrieves localized metadata for a playlist by:
 *
 * 1. Updating language of the default metadata and setting localized metadata
 *   for a playlist via "playlists.update" method.
 * 2. Getting the localized metadata for a playlist in a selected language using the
 *   "playlists.list" method and setting the "hl" parameter.
 * 3. Listing the localized metadata for a playlist using the "playlists.list" method
 *   and including "localizations" in the "part" parameter.
 *
 * @author Ibrahim Ulukaya
 */
public class PlaylistLocalizations {

    /**
     * Define a global instance of a YouTube object, which will be used to make
     * YouTube Data API requests.
     */
    private static YouTube youtube;


    /**
     * Set and retrieve localized metadata for a playlist.
     *
     * @param args command line args (not used).
     */
    public static void main(String[] args) {

        // This OAuth 2.0 access scope allows for full read/write access to the
        // authenticated user's account.
        List<String> scopes = Lists.newArrayList("https://www.googleapis.com/auth/youtube");

        try {
            // Authorize the request.
            Credential credential = Auth.authorize(scopes, "localizations");

            // This object is used to make YouTube Data API requests.
            youtube = new YouTube.Builder(Auth.HTTP_TRANSPORT, Auth.JSON_FACTORY, credential)
                    .setApplicationName("youtube-cmdline-localizations-sample").build();

            // Prompt the user to specify the action of the be achieved.
            String actionString = getActionFromUser();
            System.out.println("You chose " + actionString + ".");
            //Map the user input to the enum values.
            Action action = Action.valueOf(actionString.toUpperCase());

            switch (action) {
                case SET:
                    setPlaylistLocalization(getId("playlist"), getDefaultLanguage(),
                            getLanguage(), getMetadata("title"), getMetadata("description"));
                    break;
                case GET:
                    getPlaylistLocalization(getId("playlist"), getLanguage());
                    break;
                case LIST:
                    listPlaylistLocalizations(getId("playlist"));
                    break;
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
     * Updates a playlist's default language and sets its localized metadata.
     *
     * @param playlistId The id parameter specifies the playlist ID for the resource
     * that is being updated.
     * @param defaultLanguage The language of the playlist's default metadata
     * @param language The language of the localized metadata
     * @param title The localized title to be set
     * @param description The localized description to be set
     * @throws IOException
     */
    private static void setPlaylistLocalization(String playlistId, String defaultLanguage,
        String language, String title, String description) throws IOException {
        // Call the YouTube Data API's playlists.list method to retrieve playlists.
        PlaylistListResponse playlistListResponse = youtube.playlists().
            list("snippet,localizations").setId(playlistId).execute();

        // Since the API request specified a unique playlist ID, the API
        // response should return exactly one playlist. If the response does
        // not contain a playlist, then the specified playlist ID was not found.
        List<Playlist> playlistList = playlistListResponse.getItems();
        if (playlistList.isEmpty()) {
            System.out.println("Can't find a playlist with ID: " + playlistId);
            return;
        }
        Playlist playlist = playlistList.get(0);

        // Modify playlist's default language and localizations properties.
        // Ensure that a value is set for the resource's snippet.defaultLanguage property.
        playlist.getSnippet().setDefaultLanguage(defaultLanguage);

        // Preserve any localizations already associated with the playlist. If the
        // playlist does not have any localizations, create a new array. Append the
        // provided localization to the list of localizations associated with the playlist.
        Map<String, PlaylistLocalization> localizations = playlist.getLocalizations();
        if (localizations == null) {
            localizations = new ArrayMap<String, PlaylistLocalization>();
            playlist.setLocalizations(localizations);
        }
        PlaylistLocalization playlistLocalization = new PlaylistLocalization();
        playlistLocalization.setTitle(title);
        playlistLocalization.setDescription(description);
        localizations.put(language, playlistLocalization);

        // Update the playlist resource by calling the playlists.update() method.
        Playlist playlistResponse = youtube.playlists().update("snippet,localizations", playlist)
            .execute();

        // Print information from the API response.
        System.out.println("\n================== Updated Playlist ==================\n");
        System.out.println("  - ID: " + playlistResponse.getId());
        System.out.println("  - Default Language: " +
            playlistResponse.getSnippet().getDefaultLanguage());
        System.out.println("  - Title(" + language + "): " +
            playlistResponse.getLocalizations().get(language).getTitle());
        System.out.println("  - Description(" + language + "): " +
            playlistResponse.getLocalizations().get(language).getDescription());
        System.out.println("\n-------------------------------------------------------------\n");
    }

    /**
     * Returns localized metadata for a playlist in a selected language.
     * If the localized text is not available in the requested language,
     * this method will return text in the default language.
     *
     * @param playlistId The id parameter specifies the playlist ID for the resource
     * that is being updated.
     * @param language The language of the localized metadata
     * @throws IOException
     */
    private static void getPlaylistLocalization(String playlistId, String language) throws IOException {
        // Call the YouTube Data API's playlists.list method to retrieve playlists.
        PlaylistListResponse playlistListResponse = youtube.playlists().
            list("snippet").setId(playlistId).set("hl", language).execute();

        // Since the API request specified a unique playlist ID, the API
        // response should return exactly one playlist. If the response does
        // not contain a playlist, then the specified playlist ID was not found.
        List<Playlist> playlistList = playlistListResponse.getItems();
        if (playlistList.isEmpty()) {
            System.out.println("Can't find a playlist with ID: " + playlistId);
            return;
        }
        Playlist playlist = playlistList.get(0);

        // Print information from the API response.
        System.out.println("\n================== Playlist ==================\n");
        System.out.println("  - ID: " + playlist.getId());
        System.out.println("  - Title(" + language + "): " +
            playlist.getLocalizations().get(language).getTitle());
        System.out.println("  - Description(" + language + "): " +
            playlist.getLocalizations().get(language).getDescription());
        System.out.println("\n-------------------------------------------------------------\n");
    }

    /**
     * Returns a list of localized metadata for a playlist.
     *
     * @param playlistId The id parameter specifies the playlist ID for the resource
     * that is being updated.
     * @throws IOException
     */
    private static void listPlaylistLocalizations(String playlistId) throws IOException {
        // Call the YouTube Data API's playlists.list method to retrieve playlists.
        PlaylistListResponse playlistListResponse = youtube.playlists().
            list("snippet,localizations").setId(playlistId).execute();

        // Since the API request specified a unique playlist ID, the API
        // response should return exactly one playlist. If the response does
        // not contain a playlist, then the specified playlist ID was not found.
        List<Playlist> playlistList = playlistListResponse.getItems();
        if (playlistList.isEmpty()) {
            System.out.println("Can't find a playlist with ID: " + playlistId);
            return;
        }
        Playlist playlist = playlistList.get(0);
        Map<String, PlaylistLocalization> localizations = playlist.getLocalizations();

        // Print information from the API response.
        System.out.println("\n================== Playlist ==================\n");
        System.out.println("  - ID: " + playlist.getId());
        for (String language : localizations.keySet()) {
            System.out.println("  - Title(" + language + "): " +
                localizations.get(language).getTitle());
            System.out.println("  - Description(" + language + "): " +
                localizations.get(language).getDescription());
        }
        System.out.println("\n-------------------------------------------------------------\n");
    }

    /*
     * Prompt the user to enter a resource ID. Then return the ID.
     */
    private static String getId(String resource) throws IOException {

        String id = "";

        System.out.print("Please enter a " + resource + " id: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        id = bReader.readLine();

        System.out.println("You chose " + id + " for localizations.");
        return id;
    }

    /*
     * Prompt the user to enter the localized metadata. Then return the metadata.
     */
    private static String getMetadata(String type) throws IOException {

        String metadata = "";

        System.out.print("Please enter a localized " + type + ": ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        metadata = bReader.readLine();

        if (metadata.length() < 1) {
            // If nothing is entered, defaults to type.
          metadata = type + "(localized)";
        }

        System.out.println("You chose " + metadata + " as localized "+ type + ".");
        return metadata;
    }

    /*
     * Prompt the user to enter the language for the resource's default metadata.
     * Then return the language.
     */
    private static String getDefaultLanguage() throws IOException {

        String defaultlanguage = "";

        System.out.print("Please enter the language for the resource's default metadata: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        defaultlanguage = bReader.readLine();

        if (defaultlanguage.length() < 1) {
            // If nothing is entered, defaults to "en".
          defaultlanguage = "en";
        }

        System.out.println("You chose " + defaultlanguage +
            " as the language for the resource's default metadata.");
        return defaultlanguage;
    }

    /*
     * Prompt the user to enter a language for the localized metadata. Then return the language.
     */
    private static String getLanguage() throws IOException {

        String language = "";

        System.out.print("Please enter the localized metadata language: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        language = bReader.readLine();

        if (language.length() < 1) {
            // If nothing is entered, defaults to "de".
            language = "de";
        }

        System.out.println("You chose " + language + " as the localized metadata language.");
        return language;
    }

    /*
     * Prompt the user to enter an action. Then return the action.
     */
    private static String getActionFromUser() throws IOException {

        String action = "";

        System.out.print("Please choose action to be accomplished: ");
        System.out.print("Options are: 'set', 'get' and 'list' ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        action = bReader.readLine();

        return action;
    }

    public enum Action {
      SET,
      GET,
      LIST
    }
}
