<?php

$client = new GoogleClient();
$client->setApplicationName($applicationName);
$client->setScopes($scopes);
$client->setAuthConfig($clientSecretPath);
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