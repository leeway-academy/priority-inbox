#!/usr/bin/php -q

<?php
require_once __DIR__ . '/vendor/autoload.php';

use Google\Client as GoogleClient;
use Google\Service\Gmail as GmailService;

if (php_sapi_name() != 'cli') {
    die('This application must be run on the command line.');
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$options = getopt('b:w:m:', ['dry-run']);

$minDelay = array_key_exists('m', $options) ? $options['m'] : 0;
$whitelistEntries = array_key_exists('w', $options) ? (is_array($options['w']) ? $options['w'] : [$options['w']]) : [];
$blacklistEntries = array_key_exists('b', $options) ? (is_array($options['b']) ? $options['b'] : [$options['b']]) : [];
$importantKeyword = array_key_exists('k', $options) ? (is_array($options['k']) ? $options['k'] : [$options['k']]) : [];

$allowedFrom = getEmailsFromList($whitelistEntries);
$notAllowedFrom = getEmailsFromList($blacklistEntries);

echo date('Y-m-d H:i:s').' allowedFrom = ' . implode(', ', $allowedFrom) . PHP_EOL;
echo date('Y-m-d H:i:s').' notAllowedFrom = ' . implode(', ', $notAllowedFrom) . PHP_EOL;

$service = new GmailService(getGoogleClient(
    $_ENV['APPLICATION_NAME'],
    $_ENV['CLIENT_SECRET_PATH'],
    [
        GmailService::MAIL_GOOGLE_COM
    ]
));

$user = 'me';

$hiddenLabelPrefix = $_ENV['HIDDEN_LABEL_PREFIX'];

try {
    $labelsResponse = $service->users_labels->listUsersLabels($user);

    if ($labels = $labelsResponse->getLabels()) {
        foreach ($labels as $label) {
            if (substr($label->getName(), 0, strlen($hiddenLabelPrefix)) == $hiddenLabelPrefix) {
                $hiddenLabelId = trim($label->getId());
                $hiddenLabelName = $label->getName();
                break;
            }
        }
    }
} catch (Excetion $e) {
    die('An error occurred: ' . $e->getMessage());
}

echo date('Y-m-d H:i:s')." Fetching messages labeled '$hiddenLabelName', id: '$hiddenLabelId'. Options received: " . implode(', ', array_keys($options)) . PHP_EOL;
$results = $service->users_messages->listUsersMessages($user, ['labelIds' => [$hiddenLabelId]]);

$messages = $results->getMessages();

$parser = new PhpMimeMailParser\Parser();

echo date('Y-m-d H:i:s').' Found ' . count($messages) . ' messages' . PHP_EOL;
foreach ($messages as $message) {
    if ($real_message = $service->users_messages->get(
        $user,
        $message->getId(),
        [
            'format' => 'raw',
        ]
    )) {
        $real_message = base64_decode(str_replace(['-', '_'], ['+', '/'], $real_message->getRaw()));
        $parser->setText($real_message);
        $from = $parser->getHeader('from');

        $d = $parser->getHeader('date');
        try {
            $sentOn = new DateTimeImmutable($d);
        } catch (Exception $e) {
            $sentOn = (new DateTimeImmutable())->sub(new DateInterval("PT" . ($minDelay + 1) . "H"));
        }
        echo date('Y-m-d H:i:s').' Came from: ' . $from . '. Date: ' . $d . PHP_EOL;
        $rightNow = new DateTimeImmutable('now', $sentOn->getTimeZone());
        $elapsed = $rightNow->diff($sentOn, true)->h;

        if (
            $elapsed >= $minDelay &&
            (empty($allowedFrom) || senderBelongs($from, $allowedFrom)) &&
            !senderBelongs($from, $notAllowedFrom)
        ) {
            if (!array_key_exists('dry-run', $options)) {
                moveToInbox($service, $user, $message, $hiddenLabelId);
            }
        }
    }
}

function moveToInbox(Google_Service_Gmail $service, string $user, $message, string $hiddenLabelId)
{
    echo date('Y-m-d H:i:s').' Moving to Inbox' . PHP_EOL;
    $mods = new Google_Service_Gmail_ModifyMessageRequest();
    $mods->setAddLabelIds(['INBOX']);
    $mods->setRemoveLabelIds([$hiddenLabelId]);
    try {
        $message = $service->users_messages->modify($user, $message->getId(), $mods);
        print date('Y-m-d H:i:s').' Message with ID: ' . $message->getId() . ' successfully modified.' . PHP_EOL;
    } catch (Exception $e) {
        print date('Y-m-d H:i:s').' An error occurred: ' . $e->getMessage() . PHP_EOL;
    }
}

function senderBelongs(string $from, array $importantSenders): bool
{
    foreach ($importantSenders as $importantSender) {
        if ($importantSender && (strpos(strtolower($from), $importantSender) !== false)) {

            return true;
        }
    }

    return false;
}

function getGoogleClient(string $applicationName, string $clientSecretPath, array $scopes): GoogleClient
{
    $client = new GoogleClient();
    $client->setApplicationName($applicationName);
    $client->setScopes($scopes);
    $client->setAuthConfig($clientSecretPath);
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = __DIR__.'/token.json';
    if (file_exists($credentialsPath)) {
        echo date('Y-m-d H:i:s').' Using credentials found at '.$credentialsPath.PHP_EOL;
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
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
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }

    return $client;
}

function getEmailsFromList(array $list): array
{
    $ret = [];

    foreach ($list as $listEntry) {
        if (is_readable($listEntry)) {
            $ret = array_merge($ret, array_map(function (string $s) {
                return strtolower(trim($s));
            }, file($listEntry)));
        } else {
            $ret[] = $listEntry;
        }
    }

    return $ret;
}
