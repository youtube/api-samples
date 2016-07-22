<html>
<body>
  <div>
  <h1>Youtube Samples</h1>
<?php if (!file_exists(__DIR__ . '/vendor/autoload.php')): ?>
  <h3>Library Requirements</h3>
  <ol>
    <li>1. Install composer (https://getcomposer.org)</li>
    <li>2. On the command line, change to this directory (api-samples/php)</li>
    <li>3. Require the google/apiclient library</li>
    $ composer require google/apiclient:~2.0
  </ol>
  <strong>please run <code>composer require google/apiclient:~2.0</code> in <code>"<?php echo __DIR__  ?>"</code></strong>
<?php endif ?>
  <ul>
      <li><a href="add_channel_section.php">Add Channel Section</a></li>
      <li><a href="add_subscription.php">Add Subscription</a></li>
      <li><a href="captions.php">Captions</a></li>
      <li><a href="comment_handling.php">Comment Handling</a></li>
      <li><a href="comment_threads.php">Comment Threads</a></li>
      <li><a href="create_broadcast.php">Create Broadcast</a></li>
      <li><a href="create_reporting_job.php">Create Reporting Job</a></li>
      <li><a href="geolocation_search.php">Geolocation Search</a></li>
      <li><a href="list_broadcasts.php">List Broadcasts</a></li>
      <li><a href="list_streams.php">List Streams</a></li>
      <li><a href="my_uploads.php">My Uploads</a></li>
      <li><a href="playlist_updates.php">Playlist Updates</a></li>
      <li><a href="resumable_upload.php">Resumable Upload</a></li>
      <li><a href="retrieve_reports.php">Retrieve Reports</a></li>
      <li><a href="search.php">Search</a></li>
      <li><a href="shuffle_channel_sections.php">Shuffle Channel Sections</a></li>
      <li><a href="update_video.php">Update Video</a></li>
      <li><a href="upload_banner.php">Upload Banner</a></li>
      <li><a href="upload_thumbnail.php">Upload Thumbnail</a></li>
  </ul>
</body>
</html>
