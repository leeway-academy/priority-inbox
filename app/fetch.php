#!/usr/bin/php -q

<?php
require_once __DIR__ . '/vendor/autoload.php';

use Google\Client as GoogleClient;
use Google\Service\Gmail as GmailService;
use Google\Service\Gmail\Message as GmailMessage;
use PhpMimeMailParser\Parser;

validateRunningFromCLI();
loadEnvironmentVariables();

$options = getCLIOptions();

$minDelay = getMinimumDelay($options);
$importantKeyword = getImportantKeywords($options);
$allowedFrom = getEmailsFromList(getWhiteListEntries($options));
$notAllowedFrom = getEmailsFromList(getBlackListEntries($options));

logMessage("Allowing emails sent at least $minDelay hours ago");

if (!empty($allowedFrom)) {
    logMessage("Only allowing emails sent by " . implode(', ', $allowedFrom));
}

if (!empty($notAllowedFrom)) {
    logMessage("Only allowing emails NOT sent by " . implode(', ', $notAllowedFrom));
}

try {
    $gmailService = buildGmailService();
} catch (\Google\Exception $e) {
    die($e->getMessage());
}
list($hiddenLabelId, $hiddenLabelName) = getHiddenLabelInformation();

logMessage("Fetching messages labeled '$hiddenLabelName', id: '$hiddenLabelId'");

try {
    processMessages($gmailService, getMinimumDelay($options), $allowedFrom, $notAllowedFrom, $options, $hiddenLabelId);
} catch (Exception $e) {
    die($e->getMessage());
}

/**** Functions *****/

/**
 * @param GmailService $gmailService
 * @param int $minDelay
 * @param array $allowedFrom
 * @param array $notAllowedFrom
 * @param array|bool $options
 * @param mixed $hiddenLabelId
 * @return void
 * @throws Exception
 */
function processMessages(GmailService $gmailService, int $minDelay, array $allowedFrom, array $notAllowedFrom, array|bool $options, mixed $hiddenLabelId): void
{
    $parser = new PhpMimeMailParser\Parser();
    $messages = getHiddenMessages($gmailService, $hiddenLabelId);

    logMessage('Found ' . count($messages) . ' messages');
    foreach ($messages as $message) {
        processMessage($message, $gmailService, $parser, $minDelay, $allowedFrom, $notAllowedFrom, $options, $hiddenLabelId);
    }
}

/**
 * @param DateTimeImmutable $sentOn
 * @param int $minDelay
 * @return bool
 * @throws Exception
 */
function wasSentWithinAcceptableTimeFrame(DateTimeImmutable $sentOn, int $minDelay): bool
{
    return getTimeSinceEmailWasSent($sentOn) >= $minDelay;
}

/**
 * @param DateTimeImmutable $sentOn
 * @return int
 * @throws Exception
 */
function getTimeSinceEmailWasSent(DateTimeImmutable $sentOn): int
{
    $rightNow = new DateTimeImmutable('now', $sentOn->getTimeZone());
    $elapsed = $rightNow->diff($sentOn, true)->h;
    return $elapsed;
}

/**
 * @param bool|string $d
 * @param int $minDelay
 * @return DateTimeImmutable
 * @throws Exception
 */
function getSentDate(bool|string $d, int $minDelay): DateTimeImmutable
{
    try {
        $sentOn = new DateTimeImmutable($d);
    } catch (Exception $e) {
        $sentOn = (new DateTimeImmutable())->sub(new DateInterval("PT" . ($minDelay + 1) . "H"));
    }
    return $sentOn;
}

/**
 * @param GmailService $gmailService
 * @param string $messageId
 * @return GmailMessage
 */

function getMessage(GmailService $gmailService, string $messageId): GmailMessage
{
    return $gmailService
        ->users_messages
        ->get(
            'me',
            $messageId,
            [
                'format' => 'raw',
            ]
        );
}

/**
 * @param GmailMessage $real_message
 * @return false|string
 */
function decodeMessage(GmailMessage $real_message): string|false
{
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $real_message->getRaw()));
}

/**
 * @param GmailMessage $message
 * @param Google_Service_Gmail $service
 * @param string $hiddenLabelId
 * @return void
 */
function moveToInbox(GmailMessage $message, Google_Service_Gmail $service, string $hiddenLabelId): void
{
    logMessage('Moving to Inbox');
    $mods = new Google_Service_Gmail_ModifyMessageRequest();
    $mods->setAddLabelIds(['INBOX']);
    $mods->setRemoveLabelIds([$hiddenLabelId]);
    $service
        ->users_messages
        ->modify('me',
            $message->getId(),
            $mods);
    logMessage(' Message with ID: ' . $message->getId() . ' successfully modified.');
}

/**
 * @param array $importantSenders
 * @param string $from
 * @return bool
 */
function senderBelongs(array $importantSenders, string $from): bool
{
    return count(
            array_filter($importantSenders, fn(string $sender) => $sender && (str_contains(strtolower($from), $sender)))
        ) > 0;
}

/**
 * @param string $applicationName
 * @param string $clientSecretPath
 * @param array $scopes
 * @return GoogleClient
 * @throws \Google\Exception
 */
function getGoogleClient(string $applicationName, string $clientSecretPath, array $scopes): GoogleClient
{
    $client = new GoogleClient();
    $client->setApplicationName($applicationName);
    $client->setScopes($scopes);
    $client->setAuthConfig($clientSecretPath);
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = __DIR__ . '/token.json';
    if (file_exists($credentialsPath)) {
        echo date('Y-m-d H:i:s') . ' Using credentials found at ' . $credentialsPath . PHP_EOL;
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
 * @param array $list
 * @return array
 */
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

/**
 * @return void
 */
function validateRunningFromCLI(): void
{
    if (php_sapi_name() != 'cli') {
        die('This application must be run on the command line.');
    }
}

/**
 * @return void
 */
function loadEnvironmentVariables(): void
{
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

/**
 * @param array $options
 * @return int
 */
function getMinimumDelay(array $options): int
{
    return array_key_exists('m', $options) ? $options['m'] : 0;
}

/**
 * @param array|bool $options
 * @return array
 */
function getImportantKeywords(array|bool $options): array
{
    return array_key_exists('k', $options) ? (is_array($options['k']) ? $options['k'] : [$options['k']]) : [];
}

/**
 * @param array|bool $options
 * @return array
 */
function getWhiteListEntries(array|bool $options): array
{
    return array_key_exists('w', $options) ? (is_array($options['w']) ? $options['w'] : [$options['w']]) : [];
}

/**
 * @param array|bool $options
 * @return array
 */
function getBlackListEntries(array|bool $options): array
{
    return array_key_exists('b', $options) ? (is_array($options['b']) ? $options['b'] : [$options['b']]) : [];
}

/**
 * @return GmailService
 */
function buildGmailService(): GmailService
{
    return new GmailService(getGoogleClient(
        $_ENV['APPLICATION_NAME'],
        $_ENV['CLIENT_SECRET_PATH'],
        [
            GmailService::MAIL_GOOGLE_COM
        ]
    ));
}

/**
 * @return array
 */
function getHiddenLabelInformation(): array
{
    return array($_ENV['HIDDEN_LABEL_ID'], $_ENV['HIDDEN_LABEL_NAME']);
}

/**
 * @param GmailService $gmailService
 * @param mixed $hiddenLabelId
 * @return GmailMessage[]
 */
function getHiddenMessages(GmailService $gmailService, mixed $hiddenLabelId): array
{
    $results = $gmailService
        ->users_messages
        ->listUsersMessages('me', [
            'labelIds' =>
                [$hiddenLabelId]
        ]);

    return $results->getMessages();
}

/**
 * @param GmailMessage $message
 * @param GmailService $gmailService
 * @param Parser $parser
 * @param int $minDelay
 * @param array $allowedFrom
 * @param array $notAllowedFrom
 * @param array|bool $options
 * @param mixed $hiddenLabelId
 * @return void
 * @throws Exception
 */
function processMessage(GmailMessage $message, GmailService $gmailService, Parser $parser, int $minDelay, array $allowedFrom, array $notAllowedFrom, array|bool $options, mixed $hiddenLabelId): void
{
    if ($fullMessage = getMessage($gmailService, $message->getId())) {
        $parser->setText(decodeMessage($fullMessage));
        $from = $parser->getHeader('from');
        $sentOn = getSentDate($parser->getHeader('date'), $minDelay);

        logMessage('Came from: ' . $from . '. Date: ' . $sentOn->format('d-M-Y'));

        if (shouldMessageBeMovedToInbox($sentOn, $from, $minDelay, $allowedFrom, $notAllowedFrom)) {
            if (!isDryRun($options)) {
                moveToInbox($message, $gmailService, $hiddenLabelId);
            }
        }
    }
}

/**
 * @param array $options
 * @return bool
 */
function isDryRun(array $options): bool
{
    return array_key_exists('dry-run', $options);
}

/**
 * @param DateTimeImmutable $sentOn
 * @param string $from
 * @param int $minDelay
 * @param array $allowedFrom
 * @param array $notAllowedFrom
 * @return bool
 * @throws Exception
 */
function shouldMessageBeMovedToInbox(DateTimeImmutable $sentOn, string $from, int $minDelay, array $allowedFrom, array $notAllowedFrom): bool
{
    return wasSentWithinAcceptableTimeFrame($sentOn, $minDelay) && wasSentByAllowedSender($from, $allowedFrom, $notAllowedFrom);
}

/**
 * @param string $sender
 * @param array $allowedFrom
 * @param array $notAllowedFrom
 * @return bool
 */
function wasSentByAllowedSender(string $sender, array $allowedFrom, array $notAllowedFrom): bool
{
    return (empty($allowedFrom) || senderBelongs($allowedFrom, $sender)) && (empty($notAllowedFrom) || !senderBelongs($notAllowedFrom, $sender));
}

/**
 * @return array|false|false[]|string[]
 */
function getCLIOptions(): array|false
{
    return getopt('b:w:m:', ['dry-run']);
}

/**
 * @param string $message
 * @return void
 */
function logMessage(string $message): void
{
    echo date('Y-m-d H:i:s') . " fetch.php" . PHP_EOL;
}
