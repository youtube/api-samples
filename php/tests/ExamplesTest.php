<?php

require_once __DIR__ . '/../vendor/autoload.php';

/**
*
*/
class ExamplesTest extends PHPUnit_Framework_TestCase
{
    private $tmpFiles = array();
    private $clientId;
    private $clientSecret;
    private $accessToken;

    public function setUp()
    {
        $this->clientId = getenv('GOOGLE_CLIENT_ID');
        $this->clientSecret = getenv('GOOGLE_CLIENT_SECRET');

        // load access token if applicable
        if ($this->accessToken !== false) {
            $this->accessToken = $this->checkToken();
        }

        // spoof web host
        $_SERVER['HTTP_HOST'] = 'testhost';
    }

    /**
     * @dataProvider provideFileNamesWithIndex
     */
    public function testSyntax($file)
    {
        $file = __DIR__ . '/../' . $file;
        exec(sprintf('php -l %s', escapeshellarg($file)), $output, $return_var);
        $this->assertEquals(0, $return_var);
    }

    /**
     * @dataProvider provideFileNames
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @expectedException Exception
     * @expectedExceptionMessage please run "composer require google/apiclient:~2.0"
     */
    public function testComposerException($file)
    {
        if (!$this->clientId || !$this->clientSecret) {
            $this->markTestSkipped(
                'You must set the GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET
                environment variables'
            );
        }
        $file = __DIR__ . '/../' . $file;
        $tmpFile = $this->copyFile($file, false);

        require $tmpFile;
    }

    /**
     * @dataProvider provideFileNames
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testNoClientAuthentication($file)
    {
        if (!$this->clientId || !$this->clientSecret) {
            $this->markTestSkipped(
                'You must set the GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET
                environment variables'
            );
        }
        $file = __DIR__ . '/../' . $file;
        $contents = file_get_contents($file);
        if (false === strpos($contents, '$OAUTH2_CLIENT_ID')) {
            // no client authentication required in this file
            return;
        }
        $output = $this->runFile($file);

        $this->assertContains(
            'You need to set <code>$OAUTH2_CLIENT_ID</code> and',
            $output
        );
    }

    /**
     * @dataProvider provideFileNames
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAuthorizeAccess($file)
    {
        if (!$this->clientId || !$this->clientSecret) {
            $this->markTestSkipped(
                'You must set the GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET
                environment variables'
            );
        }

        $file = __DIR__ . '/../' . $file;
        $contents = file_get_contents($file);
        if (false === strpos($contents, '$OAUTH2_CLIENT_ID')) {
            // no client authentication required in this file
            return;
        }

        $tmpFile = $this->copyFile($file);
        $this->addClientCredentialsToFile($tmpFile);

        $output = $this->runFile($tmpFile);

        $this->assertContains(
            'You need to',
            $output
        );

        $this->assertContains(
            'authorize access</a> before proceeding.<p>',
            $output
        );
    }

    /**
     * @dataProvider provideFileNames
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWithSessionToken($file)
    {
        if (!$this->clientId || !$this->clientSecret) {
            $this->markTestSkipped(
                'You must set the GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET
                environment variables'
            );
        }

        if (!$this->accessToken) {
            $this->markTestSkipped(
                'Could not fetch access token'
            );
        }

        $file = __DIR__ . '/../' . $file;
        $contents = file_get_contents($file);
        if (false === strpos($contents, '$tokenSessionKey')) {
            // no token authentication required in this file
            return;
        }

        $tmpFile = $this->copyFile($file);
        $this->addClientCredentialsToFile($tmpFile);
        $this->addTokenToFile($tmpFile, $this->accessToken);

        $output = $this->runFile($tmpFile);
        var_dump($output);

        $this->assertNotContains(
            'You need to <a href="https://accounts.google.com/o/oauth2/auth',
            $output
        );
    }

    public function provideFileNames()
    {
        $fileNames = array(
            'add_channel_section.php',
            'add_subscription.php',
            'captions.php',
            'channel_localizations.php',
            'channel_section_localizations.php',
            'comment_handling.php',
            'comment_threads.php',
            'create_broadcast.php',
            'create_reporting_job.php',
            'geolocation_search.php',
            'list_broadcasts.php',
            'list_streams.php',
            'my_uploads.php',
            'playlist_localizations.php',
            'playlist_updates.php',
            'resumable_upload.php',
            'retrieve_reports.php',
            'search.php',
            'shuffle_channel_sections.php',
            'update_video.php',
            'upload_banner.php',
            'upload_thumbnail.php',
            'video_localizations.php',
        );

        $filenameProvider = array();
        foreach ($fileNames as $file) {
            $filenameProvider[] = array($file);
        }

        return $filenameProvider;
    }

    public function provideFileNamesWithIndex()
    {
        $fileNames = $this->provideFileNames();
        $fileNames[] = array('index.php');
        return $fileNames;
    }

    private function copyFile($file, $includeComposer = true)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), $file);
        copy($file, $tmpFile);

        $tmpVendorDir = dirname($tmpFile) . '/vendor';
        if ($includeComposer) {
            @mkdir($tmpVendorDir);
            @touch($tmpVendorDir . '/autoload.php');
        } else {
            @unlink($tmpVendorDir . '/autoload.php');
            @rmdir($tmpVendorDir);
        }

        return $this->tmpFiles[] = $tmpFile;
    }

    private function runFile($file)
    {
        ob_start();
        require $file;
        return ob_get_clean();
    }

    private function addClientCredentialsToFile($file)
    {
        // set the OAuth Client ID
        $contents = file_get_contents($file);
        $contents = str_replace(
            '$OAUTH2_CLIENT_ID = \'REPLACE_ME\';',
            sprintf('$OAUTH2_CLIENT_ID = \'%s\';', $this->clientId),
            $contents
        );

        // set the OAuth Client Secret
        $contents = str_replace(
            '$OAUTH2_CLIENT_ID = \'REPLACE_ME\';',
            sprintf('$OAUTH2_CLIENT_SECRET = \'%s\';', $this->clientSecret),
            $contents
        );

        file_put_contents($file, $contents);
    }

    private function addTokenToFile(
        $file,
        array $token,
        $scope = 'https://www.googleapis.com/auth/youtube'
    ) {
        // add the token line
        $contents = file_get_contents($file);
        $tokenLine = <<<END
// START added by tests
\$_SESSION['token-%s'] = array('access_token' => '%s', 'expires_in' => %s);
// END\n
END;
        $tokenLine = sprintf($tokenLine, $scope, $token['access_token'], $token['expires_in']);
        $f = fopen($file, "r+");
        while (false !== $line = fgets($f)) {
            if (strpos($line, '$tokenSessionKey') !== false) {
                $pos = ftell($f);
                $contents = substr_replace($contents, $tokenLine, $pos, 0);
                file_put_contents($file, $contents);
                break;
            }
        }
    }

    private function checkToken()
    {
        if ($this->clientId && $this->clientSecret) {
            $path = sys_get_temp_dir().'/youtube-api-php-tests';
            if (file_exists($path)) {
                if ($token = json_decode(file_get_contents($path), true)) {
                    if ($token['created'] + $token['expires_in'] > time()) {
                        return $token;
                    }
                }
            }
            if ($token = $this->tryToGetAnAccessToken()) {
                file_put_contents($path, json_encode($token));
                return $token;
            }
        }

        return false;
    }

    private function tryToGetAnAccessToken()
    {
        $client = new Google_Client();
        $client->setApplicationName('youtube-php-tests');
        $client->addScope('https://www.googleapis.com/auth/youtube');
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri("urn:ietf:wg:oauth:2.0:oob");
        $client->setConfig('access_type', 'offline');

        $authUrl = $client->createAuthUrl();

        echo "\nPlease enter the auth code:\n";
        ob_flush();
        `open '$authUrl'`;
        $authCode = trim(fgets(STDIN));

        if ($accessToken = $client->fetchAccessTokenWithAuthCode($authCode)) {
            if (isset($accessToken['access_token'])) {
                return $accessToken;
            }
        }
    }

    public function tearDown()
    {
        foreach ($this->tmpFiles as $file) {
            unlink($file);
        }
    }
}
