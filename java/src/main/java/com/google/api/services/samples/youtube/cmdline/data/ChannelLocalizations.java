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
import com.google.api.services.youtube.model.Channel;
import com.google.api.services.youtube.model.ChannelListResponse;
import com.google.api.services.youtube.model.ChannelLocalization;
import com.google.common.collect.Lists;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.List;
import java.util.Map;

/**
 * This sample sets and retrieves localized metadata for a channel by:
 *
 * 1. Updating language of the default metadata and setting localized metadata
 *   for a channel via "channels.update" method.
 * 2. Getting the localized metadata for a channel in a selected language using the
 *   "channels.list" method and setting the "hl" parameter.
 * 3. Listing the localized metadata for a channel using "channels.list" method and
 *   including "localizations" in the "part" parameter.
 *
 * @author Ibrahim Ulukaya
 */
public class ChannelLocalizations {

    /**
     * Define a global instance of a YouTube object, which will be used to make
     * YouTube Data API requests.
     */
    private static YouTube youtube;


    /**
     * Set and retrieve localized metadata for a channel.
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
                    setChannelLocalization(getId("channel"), getDefaultLanguage(),
                            getLanguage(), getMetadata("description"));
                    break;
                case GET:
                    getChannelLocalization(getId("channel"), getLanguage());
                    break;
                case LIST:
                    listChannelLocalizations(getId("channel"));
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
     * Updates a channel's default language and sets its localized metadata.
     *
     * @param channelId The id parameter specifies the channel ID for the resource
     * that is being updated.
     * @param defaultLanguage The language of the channel's default metadata
     * @param language The language of the localized metadata
     * @param description The localized description to be set
     * @throws IOException
     */
    private static void setChannelLocalization(String channelId, String defaultLanguage,
        String language, String description) throws IOException {
        // Call the YouTube Data API's channels.list method to retrieve channels.
        ChannelListResponse channelListResponse = youtube.channels().
            list("brandingSettings,localizations").setId(channelId).execute();

        // Since the API request specified a unique channel ID, the API
        // response should return exactly one channel. If the response does
        // not contain a channel, then the specified channel ID was not found.
        List<Channel> channelList = channelListResponse.getItems();
        if (channelList.isEmpty()) {
            System.out.println("Can't find a channel with ID: " + channelId);
            return;
        }
        Channel channel = channelList.get(0);

        // Modify channel's default language and localizations properties.
        // Ensure that a value is set for the resource's snippet.defaultLanguage property.
        // To set the snippet.defaultLanguage property for a channel resource,
        // you actually need to update the brandingSettings.channel.defaultLanguage property.
        channel.getBrandingSettings().getChannel().setDefaultLanguage(defaultLanguage);

        // Preserve any localizations already associated with the channel. If the
        // channel does not have any localizations, create a new array. Append the
        // provided localization to the list of localizations associated with the channel.
        Map<String, ChannelLocalization> localizations = channel.getLocalizations();
        if (localizations == null) {
            localizations = new ArrayMap<String, ChannelLocalization>();
            channel.setLocalizations(localizations);
        }
        ChannelLocalization channelLocalization = new ChannelLocalization();
        channelLocalization.setDescription(description);
        localizations.put(language, channelLocalization);

        // Update the channel resource by calling the channels.update() method.
        Channel channelResponse = youtube.channels()
            .update("brandingSettings,localizations", channel).execute();

        // Print information from the API response.
        System.out.println("\n================== Updated Channel ==================\n");
        System.out.println("  - ID: " + channelResponse.getId());
        System.out.println("  - Default Language: " +
            channelResponse.getSnippet().getDefaultLanguage());
        System.out.println("  - Description(" + language + "): " +
            channelResponse.getLocalizations().get(language).getDescription());
        System.out.println("\n-------------------------------------------------------------\n");
    }

    /**
     * Returns localized metadata for a channel in a selected language.
     * If the localized text is not available in the requested language,
     * this method will return text in the default language.
     *
     * @param channelId The id parameter specifies the channel ID for the resource
     * that is being updated.
     * @param language The language of the localized metadata
     * @throws IOException
     */
    private static void getChannelLocalization(String channelId, String language)
        throws IOException {
        // Call the YouTube Data API's channels.list method to retrieve channels.
        ChannelListResponse channelListResponse = youtube.channels().
            list("snippet").setId(channelId).set("hl", language).execute();

        // Since the API request specified a unique channel ID, the API
        // response should return exactly one channel. If the response does
        // not contain a channel, then the specified channel ID was not found.
        List<Channel> channelList = channelListResponse.getItems();
        if (channelList.isEmpty()) {
            System.out.println("Can't find a channel with ID: " + channelId);
            return;
        }
        Channel channel = channelList.get(0);

        // Print information from the API response.
        System.out.println("\n================== Channel ==================\n");
        System.out.println("  - ID: " + channel.getId());
        System.out.println("  - Description(" + language + "): " +
            channel.getLocalizations().get(language).getDescription());
        System.out.println("\n-------------------------------------------------------------\n");
    }

    /**
     * Returns a list of localized metadata for a channel.
     *
     * @param channelId The id parameter specifies the channel ID for the resource
     * that is being updated.
     * @throws IOException
     */
    private static void listChannelLocalizations(String channelId) throws IOException {
        // Call the YouTube Data API's channels.list method to retrieve channels.
        ChannelListResponse channelListResponse = youtube.channels().
            list("snippet,localizations").setId(channelId).execute();

        // Since the API request specified a unique channel ID, the API
        // response should return exactly one channel. If the response does
        // not contain a channel, then the specified channel ID was not found.
        List<Channel> channelList = channelListResponse.getItems();
        if (channelList.isEmpty()) {
            System.out.println("Can't find a channel with ID: " + channelId);
            return;
        }
        Channel channel = channelList.get(0);
        Map<String, ChannelLocalization> localizations = channel.getLocalizations();

        // Print information from the API response.
        System.out.println("\n================== Channel ==================\n");
        System.out.println("  - ID: " + channel.getId());
        for (String language : localizations.keySet()) {
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
