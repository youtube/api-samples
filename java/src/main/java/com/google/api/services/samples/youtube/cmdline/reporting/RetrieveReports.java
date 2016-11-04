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

package com.google.api.services.samples.youtube.cmdline.reporting;

import com.google.api.client.auth.oauth2.Credential;
import com.google.api.client.googleapis.json.GoogleJsonResponseException;
import com.google.api.client.http.GenericUrl;
import com.google.api.services.samples.youtube.cmdline.Auth;
import com.google.api.services.youtubereporting.YouTubeReporting;
import com.google.api.services.youtubereporting.YouTubeReporting.Media.Download;
import com.google.api.services.youtubereporting.model.Job;
import com.google.api.services.youtubereporting.model.ListJobsResponse;
import com.google.api.services.youtubereporting.model.ListReportsResponse;
import com.google.api.services.youtubereporting.model.Report;

import com.google.common.collect.Lists;

import java.io.BufferedReader;
import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.List;

import javax.print.attribute.standard.Media;

/**
 * This sample retrieves reports created by a specific job by:
 *
 * 1. Listing the jobs using the "jobs.list" method.
 * 2. Retrieving reports using the "reports.list" method.
 *
 * @author Ibrahim Ulukaya
 */
public class RetrieveReports {

    /**
     * Define a global instance of a YouTube Reporting object, which will be used to make
     * YouTube Reporting API requests.
     */
    private static YouTubeReporting youtubeReporting;


    /**
     * Retrieve reports.
     *
     * @param args command line args (not used).
     */
    public static void main(String[] args) {

        /*
         * This OAuth 2.0 access scope allows for read access to the YouTube Analytics monetary reports for
         * authenticated user's account. Any request that retrieves earnings or ad performance metrics must
         * use this scope.
         */
        List<String> scopes = Lists.newArrayList("https://www.googleapis.com/auth/yt-analytics-monetary.readonly");

        try {
            // Authorize the request.
            Credential credential = Auth.authorize(scopes, "retrievereports");

            // This object is used to make YouTube Reporting API requests.
            youtubeReporting = new YouTubeReporting.Builder(Auth.HTTP_TRANSPORT, Auth.JSON_FACTORY, credential)
                    .setApplicationName("youtube-cmdline-retrievereports-sample").build();

            if (listReportingJobs()) {
              if(retrieveReports(getJobIdFromUser())) {
                downloadReport(getReportUrlFromUser());
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

    /**
     * Lists reporting jobs. (jobs.listJobs)
     * @return true if at least one reporting job exists
     * @throws IOException
     */
    private static boolean listReportingJobs() throws IOException {
        // Call the YouTube Reporting API's jobs.list method to retrieve reporting jobs.
        ListJobsResponse jobsListResponse = youtubeReporting.jobs().list().execute();
        List<Job> jobsList = jobsListResponse.getJobs();

        if (jobsList == null || jobsList.isEmpty()) {
          System.out.println("No jobs found.");
          return false;
        } else {
            // Print information from the API response.
            System.out.println("\n================== Reporting Jobs ==================\n");
            for (Job job : jobsList) {
                System.out.println("  - Id: " + job.getId());
                System.out.println("  - Name: " + job.getName());
                System.out.println("  - Report Type Id: " + job.getReportTypeId());
                System.out.println("\n-------------------------------------------------------------\n");
            }
        }
        return true;
    }

    /**
     * Lists reports created by a specific job. (reports.listJobsReports)
     *
     * @param jobId The ID of the job.
     * @throws IOException
     */
    private static boolean retrieveReports(String jobId)
        throws IOException {
        // Call the YouTube Reporting API's reports.list method
        // to retrieve reports created by a job.
        ListReportsResponse reportsListResponse = youtubeReporting.jobs().reports().list(jobId).execute();
        List<Report> reportslist = reportsListResponse.getReports();

        if (reportslist == null || reportslist.isEmpty()) {
            System.out.println("No reports found.");
            return false;
        } else {
            // Print information from the API response.
            System.out.println("\n============= Reports for the job " + jobId + " =============\n");
            for (Report report : reportslist) {
                System.out.println("  - Id: " + report.getId());
                System.out.println("  - From: " + report.getStartTime());
                System.out.println("  - To: " + report.getEndTime());
                System.out.println("  - Download Url: " + report.getDownloadUrl());
                System.out.println("\n-------------------------------------------------------------\n");
            }
        }
        return true;
    }

    /**
     * Download the report specified by the URL. (media.download)
     *
     * @param reportUrl The URL of the report to be downloaded.
     * @throws IOException
     */
    private static boolean downloadReport(String reportUrl)
        throws IOException {
        // Call the YouTube Reporting API's media.download method to download a report.
        Download request = youtubeReporting.media().download("");
        FileOutputStream fop = new FileOutputStream(new File("report"));
        request.getMediaHttpDownloader().download(new GenericUrl(reportUrl), fop);
        return true;
    }

    /*
     * Prompt the user to enter a job id for report retrieval. Then return the id.
     */
    private static String getJobIdFromUser() throws IOException {

        String id = "";

        System.out.print("Please enter the job id for the report retrieval: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        id = bReader.readLine();

        System.out.println("You chose " + id + " as the job Id for the report retrieval.");
        return id;
    }

    /*
     * Prompt the user to enter a URL for report download. Then return the URL.
     */
    private static String getReportUrlFromUser() throws IOException {

        String url = "";

        System.out.print("Please enter the report URL to download: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        url = bReader.readLine();

        System.out.println("You chose " + url + " as the URL to download.");
        return url;
    }}

