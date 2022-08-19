#!/usr/bin/env php

<?php

require __DIR__ . '/vendor/autoload.php';

use Google\Client as GoogleClient;
use Google\Exception as GoogleException;
use Google\Service\Gmail;
use PhpMimeMailParser\Parser;
use PriorityInbox\Command\ReleaseEmailCommand;
use PriorityInbox\Providers\GmailDAO;
use PriorityInbox\Providers\GmailRepository;
use Symfony\Component\Console\Application;

if (php_sapi_name() != 'cli') {
    die('This application must be run on the command line.');
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * @return GoogleClient|void
 */
function buildGoogleClient()
{
    $googleClient = new GoogleClient();
    $googleClient->setApplicationName($_ENV['APPLICATION_NAME']);
    $googleClient->setScopes([Gmail::MAIL_GOOGLE_COM]);
    try {
        $googleClient->setAuthConfig($_ENV['CLIENT_SECRET_PATH']);
    } catch (\Google\Exception $e) {
        die("Couldn't set auth config: " . $e->getMessage());
    }
    $googleClient->setAccessType('offline');
    $credentialsPath = $_ENV['CLIENT_CREDENTIALS_PATH'];
    try {
        loadExistingCredentialsIfPossible($credentialsPath);
    } catch (GoogleException $e) {
        die("Couldn't load existing credentials: " . $e->getMessage());
    }
    $googleClient->setAccessToken(getAccessTokenDataFrom($credentialsPath));
    refreshTokenIfExpired($googleClient, $credentialsPath);
    return $googleClient;
}

$application = new Application();
$command = new ReleaseEmailCommand(
    new GmailRepository(
        new GmailDAO(
            new Gmail(buildGoogleClient())
        ),
        new Parser()
    )
);

$application->add($command);
$application->setDefaultCommand($command->getName(), true);

$_SERVER['argv'][] = $_ENV['HIDDEN_LABEL_ID'];

try {
    $application->run();
} catch (Exception $e) {
    die($e->getMessage());
}

/**
 * @param GoogleClient $client
 * @param string $credentialsPath
 * @return void
 */
function refreshTokenIfExpired(GoogleClient $client, string $credentialsPath): void
{
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        storeAccessTokenData($credentialsPath, $client->getAccessToken());
    }
}

/**
 * @param string $credentialsPath
 * @param array $accessToken
 * @return void
 */
function storeAccessTokenData(string $credentialsPath, array $accessToken): void
{
    file_put_contents($credentialsPath, json_encode($accessToken));
}

/**
 * @param string $credentialsPath
 * @return void
 * @throws GoogleException
 */
function loadExistingCredentialsIfPossible(string $credentialsPath): void
{
    if (!file_exists($credentialsPath)) {
        throw new GoogleException("Credentials not found. Run the authorize command first");
    }
}

/**
 * @param string $credentialsPath
 * @return mixed
 */
function getAccessTokenDataFrom(string $credentialsPath): mixed
{
    return json_decode(file_get_contents($credentialsPath), true);
}