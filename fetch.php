#!/usr/bin/php -q

<?php
require_once __DIR__ . '/vendor/autoload.php';

define('APPLICATION_NAME', 'Gmail API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__ . '/credentials.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/gmail-php-quickstart.json
define('SCOPES', implode(' ', array(
        Google_Service_Gmail::MAIL_GOOGLE_COM)
));

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

$options = getopt('b:w:m:', ['dry-run']);

$minDelay = array_key_exists('m', $options) ? $options['m'] : 0;
$whitelistEntries = array_key_exists('w', $options) ? (is_array($options['w']) ? $options['w'] : [$options['w']]) : [];
$blacklistEntries = array_key_exists('b', $options) ? (is_array($options['b']) ? $options['b'] : [$options['b']]) : [];
$importantKeyword = array_key_exists('k', $options) ? (is_array($options['k']) ? $options['k'] : [$options['k']]) : [];

$allowedFrom = getEmailsFromList($whitelistEntries);
$notAllowedFrom = getEmailsFromList($blacklistEntries);

echo 'allowedFrom = ' . implode(', ', $allowedFrom) . PHP_EOL;
echo 'notAllowedFrom = ' . implode(', ', $notAllowedFrom) . PHP_EOL;

echo 'Using credentials from: ' . CREDENTIALS_PATH . PHP_EOL;

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Gmail($client);

$user = 'me';

$hiddenLabelPrefix = require_once 'hidden_label_prefix.php';

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

echo "Fetching messages labeled '$hiddenLabelName', id: '$hiddenLabelId'. Options received: " . implode(', ', array_keys($options)) . PHP_EOL;
$results = $service->users_messages->listUsersMessages($user, ['labelIds' => [$hiddenLabelId]]);

$messages = $results->getMessages();

$parser = new PhpMimeMailParser\Parser();

echo 'Found ' . count($messages) . ' messages' . PHP_EOL;
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
        echo 'Came from: ' . $from . '. Date: ' . $d . PHP_EOL;
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
    echo 'Moving to Inbox' . PHP_EOL;
    $mods = new Google_Service_Gmail_ModifyMessageRequest();
    $mods->setAddLabelIds(['INBOX']);
    $mods->setRemoveLabelIds([$hiddenLabelId]);
    try {
        $message = $service->users_messages->modify($user, $message->getId(), $mods);
        print 'Message with ID: ' . $message->getId() . ' successfully modified.' . PHP_EOL;
    } catch (Exception $e) {
        print 'An error occurred: ' . $e->getMessage() . PHP_EOL;
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

function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAuthConfig(CLIENT_SECRET_PATH);
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
    if (file_exists($credentialsPath)) {
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

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path)
{
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
    }
    return str_replace('~', realpath($homeDirectory), $path);
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
