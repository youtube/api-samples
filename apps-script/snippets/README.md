# Apps Script Code Snippets

The `youtube-data-api.gs` file in this directory contains code snippets that are generated
by the Data API code snippet tool at:
https://developers.google.com/youtube/v3/code_samples/code_snippets

You can use that tool to test different parameter values and to generate code samples with
those modified parameter values. The tool generates code for several other programming
languages as well.

Each function in the file demonstrates a particular use case for a particular API method.
For example, there are several different use cases for calling the `search.list()` method,
such as searching by keyword or searching for live events.

In addition to the use-case-specific functions, the file also contains some boilerplate code
that prints some data from an API response to the logging console. The print function is
currently designed just to show that each API response returns data and serves as a placeholder
for any function that would actually process an API response.

## Running these samples

To run these samples:

1. Create a spreadsheet in [Google Drive](https://spreadsheets.google.com).
2. Select **Tools &gt; Script Editor** from the menu bar.
3. Paste this code into the script editor and save your file.
4. In the script, select **Resources &gt; Advanced Google Services** and toggle the option for the
   YouTube Data API to on.
5. Click the link to the Google Developers Console and enable the YouTube Data API for the project.
6. Go back to the script editor and click 'OK' to indicate that you have finished enabling advanced services.
7. Run the `Main` function in your script.
8. Select **View &gt; Logs** to see the output from the script.
