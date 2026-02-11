<?php

require_once 'vendor/autoload.php';

use Google\Client as GoogleClient;
use Google\Service\Gmail;

loadEnvironmentVariables();

$client = new GoogleClient();
$client->setApplicationName($_ENV['APPLICATION_NAME']);
$client->setScopes([Gmail::GMAIL_MODIFY]);
$client->setAuthConfig($_ENV['CLIENT_SECRET_PATH']);
$client->setAccessType('offline');

// Load previously authorized credentials from a file.
$credentialsPath = __DIR__ . '/token.json';

// Request authorization from the user.
$authUrl = $client->createAuthUrl();
printf("Open the following link in your browser:\n%s\n", $authUrl);
print 'Enter verification code: ';
$authCode = trim(fgets(STDIN));

// Exchange authorization code for an access token.
$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

// Store the credentials to disk.
if (!file_exists(dirname($credentialsPath))) {
    mkdir(dirname($credentialsPath), 0700, true);
}
file_put_contents($credentialsPath, json_encode($accessToken));
printf("Credentials saved to %s\n", $credentialsPath);

function loadEnvironmentVariables(): void
{
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}