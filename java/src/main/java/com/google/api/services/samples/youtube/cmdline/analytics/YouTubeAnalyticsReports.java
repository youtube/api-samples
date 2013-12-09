package com.google.api.services.samples.youtube.cmdline.analytics;

import com.google.api.client.auth.oauth2.Credential;
import com.google.api.client.http.HttpTransport;
import com.google.api.client.http.javanet.NetHttpTransport;
import com.google.api.client.json.JsonFactory;
import com.google.api.client.json.jackson2.JacksonFactory;
import com.google.api.services.samples.youtube.cmdline.Auth;
import com.google.api.services.youtube.YouTube;
import com.google.api.services.youtube.model.Channel;
import com.google.api.services.youtube.model.ChannelListResponse;
import com.google.api.services.youtubeAnalytics.YouTubeAnalytics;
import com.google.api.services.youtubeAnalytics.model.ResultTable;
import com.google.api.services.youtubeAnalytics.model.ResultTable.ColumnHeaders;
import com.google.common.collect.Lists;

import java.io.IOException;
import java.io.PrintStream;
import java.math.BigDecimal;
import java.util.List;

/**
 * This example uses the YouTube Data and YouTube Analytics APIs to retrieve
 * YouTube Analytics data. It also uses OAuth 2.0 for authorization.
 *
 * @author Christoph Schwab-Ganser and Jeremy Walker
 */
public class YouTubeAnalyticsReports {

    /**
     * Define a global instance of the HTTP transport.
     */
    private static final HttpTransport HTTP_TRANSPORT = new NetHttpTransport();

    /**
     * Define a global instance of the JSON factory.
     */
    private static final JsonFactory JSON_FACTORY = new JacksonFactory();

    /**
     * Define a global instance of a Youtube object, which will be used
     * to make YouTube Data API requests.
     */
    private static YouTube youtube;

    /**
     * Define a global instance of a YoutubeAnalytics object, which will be
     * used to make YouTube Analytics API requests.
     */
    private static YouTubeAnalytics analytics;

    /**
     * This code authorizes the user, uses the YouTube Data API to retrieve
     * information about the user's YouTube channel, and then fetches and
     * prints statistics for the user's channel using the YouTube Analytics API.
     *
     * @param args command line args (not used).
     */
    public static void main(String[] args) {

        // These scopes are required to access information about the
        // authenticated user's YouTube channel as well as Analytics
        // data for that channel.
        List<String> scopes = Lists.newArrayList(
                "https://www.googleapis.com/auth/yt-analytics.readonly",
                "https://www.googleapis.com/auth/youtube.readonly"
        );

        try {
            // Authorize the request.
            Credential credential = Auth.authorize(scopes, "analyticsreports");

            // This object is used to make YouTube Data API requests.
            youtube = new YouTube.Builder(HTTP_TRANSPORT, JSON_FACTORY, credential)
                    .setApplicationName("youtube-analytics-api-report-example")
                    .build();

            // This object is used to make YouTube Analytics API requests.
            analytics = new YouTubeAnalytics.Builder(HTTP_TRANSPORT, JSON_FACTORY, credential)
                    .setApplicationName("youtube-analytics-api-report-example")
                    .build();

            // Construct a request to retrieve the current user's channel ID.
            YouTube.Channels.List channelRequest = youtube.channels().list("id,snippet");
            channelRequest.setMine(true);
            channelRequest.setFields("items(id,snippet/title)");
            ChannelListResponse channels = channelRequest.execute();

            // List channels associated with the user.
            List<Channel> listOfChannels = channels.getItems();

            // The user's default channel is the first item in the list.
            Channel defaultChannel = listOfChannels.get(0);
            String channelId = defaultChannel.getId();

            PrintStream writer = System.out;
            if (channelId == null) {
                writer.println("No channel found.");
            } else {
                writer.println("Default Channel: " + defaultChannel.getSnippet().getTitle() +
                        " ( " + channelId + " )\n");

                printData(writer, "Views Over Time.", executeViewsOverTimeQuery(analytics, channelId));
                printData(writer, "Top Videos", executeTopVideosQuery(analytics, channelId));
                printData(writer, "Demographics", executeDemographicsQuery(analytics, channelId));
            }
        } catch (IOException e) {
            System.err.println("IOException: " + e.getMessage());
            e.printStackTrace();
        } catch (Throwable t) {
            System.err.println("Throwable: " + t.getMessage());
            t.printStackTrace();
        }
    }

    /**
     * Retrieve the views and unique viewers per day for the channel.
     *
     * @param analytics The service object used to access the Analytics API.
     * @param id        The channel ID from which to retrieve data.
     * @return The API response.
     * @throws IOException if an API error occurred.
     */
    private static ResultTable executeViewsOverTimeQuery(YouTubeAnalytics analytics,
                                                         String id) throws IOException {

        return analytics.reports()
                .query("channel==" + id,     // channel id
                        "2012-01-01",         // Start date.
                        "2012-01-14",         // End date.
                        "views,uniques")      // Metrics.
                .setDimensions("day")
                .setSort("day")
                .execute();
    }

    /**
     * Retrieve the channel's 10 most viewed videos in descending order.
     *
     * @param analytics the analytics service object used to access the API.
     * @param id        the string id from which to retrieve data.
     * @return the response from the API.
     * @throws IOException if an API error occurred.
     */
    private static ResultTable executeTopVideosQuery(YouTubeAnalytics analytics,
                                                     String id) throws IOException {

        return analytics.reports()
                .query("channel==" + id,                          // channel id
                        "2012-01-01",                              // Start date.
                        "2012-08-14",                              // End date.
                        "views,subscribersGained,subscribersLost") // Metrics.
                .setDimensions("video")
                .setSort("-views")
                .setMaxResults(10)
                .execute();
    }

    /**
     * Retrieve the demographics report for the channel.
     *
     * @param analytics the analytics service object used to access the API.
     * @param id        the string id from which to retrieve data.
     * @return the response from the API.
     * @throws IOException if an API error occurred.
     */
    private static ResultTable executeDemographicsQuery(YouTubeAnalytics analytics,
                                                        String id) throws IOException {
        return analytics.reports()
                .query("channel==" + id,     // channel id
                        "2007-01-01",         // Start date.
                        "2012-08-14",         // End date.
                        "viewerPercentage")   // Metrics.
                .setDimensions("ageGroup,gender")
                .setSort("-viewerPercentage")
                .execute();
    }

    /**
     * Prints the API response. The channel name is printed along with
     * each column name and all the data in the rows.
     *
     * @param writer  stream to output to
     * @param title   title of the report
     * @param results data returned from the API.
     */
    private static void printData(PrintStream writer, String title, ResultTable results) {
        writer.println("Report: " + title);
        if (results.getRows() == null || results.getRows().isEmpty()) {
            writer.println("No results Found.");
        } else {

            // Print column headers.
            for (ColumnHeaders header : results.getColumnHeaders()) {
                writer.printf("%30s", header.getName());
            }
            writer.println();

            // Print actual data.
            for (List<Object> row : results.getRows()) {
                for (int colNum = 0; colNum < results.getColumnHeaders().size(); colNum++) {
                    ColumnHeaders header = results.getColumnHeaders().get(colNum);
                    Object column = row.get(colNum);
                    if ("INTEGER".equals(header.getUnknownKeys().get("dataType"))) {
                        long l = ((BigDecimal) column).longValue();
                        writer.printf("%30d", l);
                    } else if ("FLOAT".equals(header.getUnknownKeys().get("dataType"))) {
                        writer.printf("%30f", column);
                    } else if ("STRING".equals(header.getUnknownKeys().get("dataType"))) {
                        writer.printf("%30s", column);
                    } else {
                        // default output.
                        writer.printf("%30s", column);
                    }
                }
                writer.println();
            }
            writer.println();
        }
    }

}
