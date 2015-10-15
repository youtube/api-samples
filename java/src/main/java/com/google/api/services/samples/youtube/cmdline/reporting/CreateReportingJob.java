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
import com.google.api.services.samples.youtube.cmdline.Auth;
import com.google.api.services.youtubereporting.YouTubeReporting;
import com.google.api.services.youtubereporting.model.Job;
import com.google.api.services.youtubereporting.model.ListReportTypesResponse;
import com.google.api.services.youtubereporting.model.ReportType;
import com.google.common.collect.Lists;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.List;

/**
 * This sample creates a reporting job by:
 *
 * 1. Listing the available report types using the "reportTypes.list" method.
 * 2. Creating a reporting job using the "jobs.create" method.
 *
 * @author Ibrahim Ulukaya
 */
public class CreateReportingJob {

    /**
     * Define a global instance of a YouTube Reporting object, which will be used to make
     * YouTube Reporting API requests.
     */
    private static YouTubeReporting youtubeReporting;


    /**
     * Create a reporting job.
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
            Credential credential = Auth.authorize(scopes, "createreportingjob");

            // This object is used to make YouTube Reporting API requests.
            youtubeReporting = new YouTubeReporting.Builder(Auth.HTTP_TRANSPORT, Auth.JSON_FACTORY, credential)
                    .setApplicationName("youtube-cmdline-createreportingjob-sample").build();

            // Prompt the user to specify the name of the job to be created.
            String name = getNameFromUser();

            if (listReportTypes()) {
              createReportingJob(getReportTypeIdFromUser(), name);
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
     * Lists report types. (reportTypes.listReportTypes)
     * @return true if at least one report type exists
     * @throws IOException
     */
    private static boolean listReportTypes() throws IOException {
        // Call the YouTube Reporting API's reportTypes.list method to retrieve report types.
        ListReportTypesResponse reportTypesListResponse = youtubeReporting.reportTypes().list()
            .execute();
        List<ReportType> reportTypeList = reportTypesListResponse.getReportTypes();

        if (reportTypeList == null || reportTypeList.isEmpty()) {
          System.out.println("No report types found.");
          return false;
        } else {
            // Print information from the API response.
            System.out.println("\n================== Report Types ==================\n");
            for (ReportType reportType : reportTypeList) {
                System.out.println("  - Id: " + reportType.getId());
                System.out.println("  - Name: " + reportType.getName());
                System.out.println("\n-------------------------------------------------------------\n");
           }
        }
        return true;
    }

    /**
     * Creates a reporting job. (jobs.create)
     *
     * @param reportTypeId Id of the job's report type.
     * @param name name of the job.
     * @throws IOException
     */
    private static void createReportingJob(String reportTypeId, String name)
        throws IOException {
        // Create a reporting job with a name and a report type id.
        Job job = new Job();
        job.setReportTypeId(reportTypeId);
        job.setName(name);

        // Call the YouTube Reporting API's jobs.create method to create a job.
        Job createdJob = youtubeReporting.jobs().create(job).execute();

        // Print information from the API response.
        System.out.println("\n================== Created reporting job ==================\n");
        System.out.println("  - ID: " + createdJob.getId());
        System.out.println("  - Name: " + createdJob.getName());
        System.out.println("  - Report Type Id: " + createdJob.getReportTypeId());
        System.out.println("  - Create Time: " + createdJob.getCreateTime());
        System.out.println("\n-------------------------------------------------------------\n");
    }

    /*
     * Prompt the user to enter a name for the job. Then return the name.
     */
    private static String getNameFromUser() throws IOException {

        String name = "";

        System.out.print("Please enter the name for the job [javaTestJob]: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        name = bReader.readLine();

        if (name.length() < 1) {
            // If nothing is entered, defaults to "javaTestJob".
          name = "javaTestJob";
        }

        System.out.println("You chose " + name + " as the name for the job.");
        return name;
    }

    /*
     * Prompt the user to enter a report type id for the job. Then return the id.
     */
    private static String getReportTypeIdFromUser() throws IOException {

        String id = "";

        System.out.print("Please enter the reportTypeId for the job: ");
        BufferedReader bReader = new BufferedReader(new InputStreamReader(System.in));
        id = bReader.readLine();

        System.out.println("You chose " + id + " as the report type Id for the job.");
        return id;
    }
}
