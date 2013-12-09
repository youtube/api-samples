/*
 * Copyright (c) 2012 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under the
 * License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing permissions and
 * limitations under the License.
 */

package com.google.api.services.samples.youtube.cmdline.data;

import com.google.api.client.googleapis.json.GoogleJsonResponseException;
import com.google.api.client.http.HttpRequest;
import com.google.api.client.http.HttpRequestInitializer;
import com.google.api.services.samples.youtube.cmdline.Auth;
import com.google.api.services.youtube.YouTube;
import com.google.api.services.youtube.model.ResourceId;
import com.google.api.services.youtube.model.SearchListResponse;
import com.google.api.services.youtube.model.SearchResult;
import com.google.api.services.youtube.model.Thumbnail;
import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.HttpClient;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.client.utils.URLEncodedUtils;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;
import org.codehaus.jackson.JsonNode;
import org.codehaus.jackson.map.ObjectMapper;
import org.codehaus.jackson.node.ArrayNode;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;
import java.util.Properties;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * This application demonstrates a semantic YouTube search that prompts the
 * user to enter a search term and select a topic related to that term. The
 * class calls the Freebase API to get a topic ID, then passes that id along
 * with another user query term to the YouTube APIs. The result is a list of
 * videos based on a semantic search.
 *
 * @author Jeremy Walker
 */
public class Topics {

    /**
     * Define a global variable that identifies the name of a file that
     * contains the developer's API key.
     */
    private static final String PROPERTIES_FILENAME = "youtube.properties";

    /**
     * Define a global variable that specifies the maximum number of videos
     * that an API response can contain.
     */
    private static final long NUMBER_OF_VIDEOS_RETURNED = 5;

    /**
     * Define a global variable that specifies the maximum number of topics
     * that an API response can contain.
     */
    private static final long NUMBER_OF_TOPICS_RETURNED = 5;

    /**
     * Define a global instance of a Youtube object, which will be used
     * to make YouTube Data API requests.
     */
    private static YouTube youtube;

    /**
     * Execute a search request that starts by calling the Freebase API to
     * retrieve a topic ID matching a user-provided term. Then initialize a
     * YouTube object to search for YouTube videos and call the YouTube Data
     * API's youtube.search.list method to retrieve a list of videos associated
     * with the selected Freebase topic and with another search query term,
     * which the user also enters. Finally, display the titles, video IDs, and
     * thumbnail images for the first five videos in the YouTube Data API
     * response.
     *
     * @param args This application does not use command line arguments.
     */
    public static void main(String[] args) {
        // Read the developer key from the properties file.
        Properties properties = new Properties();
        try {
            InputStream in = Topics.class.getResourceAsStream("/" + PROPERTIES_FILENAME);
            properties.load(in);

        } catch (IOException e) {
            System.err.println("There was an error reading " + PROPERTIES_FILENAME + ": " + e.getCause()
                    + " : " + e.getMessage());
            System.exit(1);
        }


        try {
            // Retrieve a Freebase topic ID based on a user-entered query term.
            String topicsId = getTopicId();
            if (topicsId.length() < 1) {
                System.out.println("No topic id will be applied to your search.");
            }

            // Prompt the user to enter a search query term. This term will be
            // used to retrieve YouTube search results related to the topic
            // selected above.
            String queryTerm = getInputQuery("search");

            // This object is used to make YouTube Data API requests. The last
            // argument is required, but since we don't need anything
            // initialized when the HttpRequest is initialized, we override
            // the interface and provide a no-op function.
            youtube = new YouTube.Builder(Auth.HTTP_TRANSPORT, Auth.JSON_FACTORY, new HttpRequestInitializer() {
                public void initialize(HttpRequest request) throws IOException {
                }
            })
            .setApplicationName("youtube-cmdline-search-sample")
            .build();

            YouTube.Search.List search = youtube.search().list("id,snippet");

            // Set your developer key from the {{ Google Cloud Console }} for
            // non-authenticated requests. See:
            // {{ https://cloud.google.com/console }}
            String apiKey = properties.getProperty("youtube.apikey");
            search.setKey(apiKey);
            search.setQ(queryTerm);
            if (topicsId.length() > 0) {
                search.setTopicId(topicsId);
            }

            // Restrict the search results to only include videos. See:
            // https://developers.google.com/youtube/v3/docs/search/list#type
            search.setType("video");

            // To increase efficiency, only retrieve the fields that the
            // application uses.
            search.setFields("items(id/kind,id/videoId,snippet/title,snippet/thumbnails/default/url)");
            search.setMaxResults(NUMBER_OF_VIDEOS_RETURNED);
            SearchListResponse searchResponse = search.execute();

            List<SearchResult> searchResultList = searchResponse.getItems();

            if (searchResultList != null) {
                prettyPrint(searchResultList.iterator(), queryTerm, topicsId);
            } else {
                System.out.println("There were no results for your query.");
            }
        } catch (GoogleJsonResponseException e) {
            System.err.println("There was a service error: " + e.getDetails().getCode() +
                    " : " + e.getDetails().getMessage());
            e.printStackTrace();
        } catch (IOException e) {
            System.err.println("There was an IO error: " + e.getCause() + " : " + e.getMessage());
            e.printStackTrace();
        }
    }

    /*
     * Prompt the user to enter a search term and return the user's input.
     *
     * @param searchCategory This value is displayed to the user to clarify the information that the application is requesting.
     */
    private static String getInputQuery(String searchCategory) throws IOException {

        String inputQuery = "";

        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));

        do {
            System.out.print("Please enter a " + searchCategory + " term: ");
            inputQuery = bReader.readLine();
        } while (inputQuery.length() < 1);

        return inputQuery;
    }

    /**
     * Retrieve Freebase topics that match a user-provided query term. Then
     * prompt the user to select a topic and return its topic ID.
     */
    private static String getTopicId() throws IOException {

        // The application will return an empty string if no matching topic ID
        // is found or no results are available.
        String topicsId = "";

        // Prompt the user to enter a query term for finding Freebase topics.
        String topicQuery = getInputQuery("topics");

        // The Freebase Java library does not provide search functionality, so
        // the application needs to call directly against the URL. This code
        // constructs the proper URL, then uses jackson classes to convert the
        // JSON response into a Java object. You can learn more about the
        // Freebase search calls at: http://wiki.freebase.com/wiki/ApiSearch.
        HttpClient httpclient = new DefaultHttpClient();
        List<NameValuePair> params = new ArrayList<NameValuePair>();
        params.add(new BasicNameValuePair("query", topicQuery));
        params.add(new BasicNameValuePair("limit", Long.toString(NUMBER_OF_TOPICS_RETURNED)));

        String serviceURL = "https://www.googleapis.com/freebase/v1/search";
        String url = serviceURL + "?" + URLEncodedUtils.format(params, "UTF-8");

        HttpResponse httpResponse = httpclient.execute(new HttpGet(url));
        HttpEntity entity = httpResponse.getEntity();

        if (entity != null) {
            InputStream instream = entity.getContent();
            try {

                // Convert the JSON to an object. This code does not do an
                // exact map from JSON to POJO (Plain Old Java object), but
                // you could create additional classes and use them with the
                // mapper.readValue() function to get that exact mapping.
                ObjectMapper mapper = new ObjectMapper();
                JsonNode rootNode = mapper.readValue(instream, JsonNode.class);

                // Confirm that the HTTP request was handled successfully by
                // checking the API response's HTTP response code.
                if (rootNode.get("status").asText().equals("200 OK")) {
                    // In the API response, the "result" field contains the
                    // list of needed results.
                    ArrayNode arrayNodeResults = (ArrayNode) rootNode.get("result");
                    // Prompt the user to select a topic from the list of API
                    // results.
                    topicsId = getUserChoice(arrayNodeResults);
                }
            } finally {
                instream.close();
            }
        }
        return topicsId;
    }

    /**
     * Output results from the Freebase topic search to the user, prompt the
     * user to select a topic, and return the appropriate topic ID.
     *
     * @param freebaseResults ArrayNode This object contains the search results.
     */
    private static String getUserChoice(ArrayNode freebaseResults) throws IOException {

        String freebaseId = "";

        if (freebaseResults.size() < 1) {
            return freebaseId;
        }

        // Print a list of topics retrieved from Freebase.
        for (int i = 0; i < freebaseResults.size(); i++) {
            JsonNode node = freebaseResults.get(i);
            System.out.print(" " + i + " = " + node.get("name").asText());
            if (node.get("notable") != null) {
                System.out.print(" (" + node.get("notable").get("name").asText() + ")");
            }
            System.out.println("");
        }

        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        String inputChoice;

        // Prompt the user to select a topic.
        do {
            System.out.print("Choose the number of the Freebase Node: ");
            inputChoice = bReader.readLine();
        } while (!isValidIntegerSelection(inputChoice, freebaseResults.size()));

        // Return the topic ID needed for the API request that retrieves
        // YouTube search results.
        JsonNode node = freebaseResults.get(Integer.parseInt(inputChoice));
        freebaseId = node.get("mid").asText();
        return freebaseId;
    }

    /**
     * Confirm that the string represents a valid, positive integer, that is
     * less than or equal to 999,999,999. (Since the API will not return a
     * billion results for a query, any integer that the user enters will need
     * to be less than that to be valid, anyway.)
     *
     * @param input The string to test.
     * @param max   The integer must be less then this maximum number.
     */
    public static boolean isValidIntegerSelection(String input, int max) {
        if (input.length() > 9)
            return false;

        boolean validNumber = false;
        // Only accept positive numbers with a maximum of nine digits.
        Pattern intsOnly = Pattern.compile("^\\d{1,9}$");
        Matcher makeMatch = intsOnly.matcher(input);

        if (makeMatch.find()) {
            int number = Integer.parseInt(makeMatch.group());
            if ((number >= 0) && (number < max)) {
                validNumber = true;
            }
        }
        return validNumber;
    }

    /*
     * Prints out all results in the Iterator. For each result, print the
     * title, video ID, and thumbnail.
     *
     * @param iteratorSearchResults Iterator of SearchResults to print
     * @param query Search query (String)
     */
    private static void prettyPrint(Iterator<SearchResult> iteratorSearchResults, String query, String topicsId) {

        System.out.println("\n=============================================================");
        System.out.println("   First " + NUMBER_OF_VIDEOS_RETURNED + " videos for search on \"" + query + "\" with Topics id: " + topicsId + ".");
        System.out.println("=============================================================\n");

        if (!iteratorSearchResults.hasNext()) {
            System.out.println(" There aren't any results for your query.");
        }

        while (iteratorSearchResults.hasNext()) {

            SearchResult singleVideo = iteratorSearchResults.next();
            ResourceId rId = singleVideo.getId();

            // Confirm that the result represents a video. Otherwise, the
            // item will not contain a video ID.
            if (rId.getKind().equals("youtube#video")) {
                Thumbnail thumbnail = singleVideo.getSnippet().getThumbnails().getDefault();

                System.out.println(" Video Id" + rId.getVideoId());
                System.out.println(" Title: " + singleVideo.getSnippet().getTitle());
                System.out.println(" Thumbnail: " + thumbnail.getUrl());
                System.out.println("\n-------------------------------------------------------------\n");
            }
        }
    }
}
