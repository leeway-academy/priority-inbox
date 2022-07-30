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

echo date('Y-m-d H:i:s') . ' allowedFrom = ' . implode(', ', $allowedFrom) . PHP_EOL;
echo date('Y-m-d H:i:s') . ' notAllowedFrom = ' . implode(', ', $notAllowedFrom) . PHP_EOL;

$gmailService = buildGmailService();
$user = 'me';
list($hiddenLabelId, $hiddenLabelName) = getHiddenLabelInformation($gmailService, $user, $_ENV['HIDDEN_LABEL_PREFIX']);

echo date('Y-m-d H:i:s') . " Fetching messages labeled '$hiddenLabelName', id: '$hiddenLabelId'. Options received: " . implode(', ', array_keys($options)) . PHP_EOL;

$parser = new PhpMimeMailParser\Parser();

$messages = getHiddenMessages($gmailService, $user, $hiddenLabelId);

echo date('Y-m-d H:i:s') . ' Found ' . count($messages) . ' messages' . PHP_EOL;

processMessages($messages, $gmailService, $user, $parser, $minDelay, $allowedFrom, $notAllowedFrom, $options, $hiddenLabelId);

/**
 * @param array $messages
 * @param GmailService $gmailService
 * @param string $user
 * @param Parser $parser
 * @param int $minDelay
 * @param array $allowedFrom
 * @param array $notAllowedFrom
 * @param array|bool $options
 * @param mixed $hiddenLabelId
 * @return void
 * @throws Exception
 */
function processMessages(array $messages, GmailService $gmailService, string $user, Parser $parser, int $minDelay, array $allowedFrom, array $notAllowedFrom, array|bool $options, mixed $hiddenLabelId): void
{
    foreach ($messages as $message) {
        processMessage($message, $gmailService, $user, $parser, $minDelay, $allowedFrom, $notAllowedFrom, $options, $hiddenLabelId);
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
 * @param string $user
 * @param GmailMessage $message
 * @return GmailMessage
 */

function getMessage(GmailService $gmailService, string $user, GmailMessage $message): GmailMessage
{
    return $gmailService
        ->users_messages
        ->get(
            $user,
            $message->getId(),
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
 * @param string $user
 * @param string $hiddenLabelId
 * @return void
 */
function moveToInbox(GmailMessage $message, Google_Service_Gmail $service, string $user, string $hiddenLabelId): void
{
    echo date('Y-m-d H:i:s') . ' Moving to Inbox' . PHP_EOL;
    $mods = new Google_Service_Gmail_ModifyMessageRequest();
    $mods->setAddLabelIds(['INBOX']);
    $mods->setRemoveLabelIds([$hiddenLabelId]);
    $message = $service->users_messages->modify($user, $message->getId(), $mods);
    print date('Y-m-d H:i:s') . ' Message with ID: ' . $message->getId() . ' successfully modified.' . PHP_EOL;
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
 * @param GmailService $gmailService
 * @param string $user
 * @param mixed $hiddenLabelPrefix
 * @return array
 */
function getHiddenLabelInformation(GmailService $gmailService, string $user, mixed $hiddenLabelPrefix): array
{
    $labelsResponse = $gmailService->users_labels->listUsersLabels($user);

    if ($labels = $labelsResponse->getLabels()) {
        foreach ($labels as $label) {
            if (str_starts_with($label->getName(), $hiddenLabelPrefix)) {
                $hiddenLabelId = trim($label->getId());
                $hiddenLabelName = $label->getName();

                break;
            }
        }
    }

    return array($hiddenLabelId, $hiddenLabelName);
}

/**
 * @param GmailService $gmailService
 * @param string $user
 * @param mixed $hiddenLabelId
 * @return GmailMessage[]
 */
function getHiddenMessages(GmailService $gmailService, string $user, mixed $hiddenLabelId): array
{
    $results = $gmailService
        ->users_messages
        ->listUsersMessages($user, [
            'labelIds' =>
                [$hiddenLabelId]
        ]);

    return $results->getMessages();
}

/**
 * @param GmailMessage $message
 * @param GmailService $gmailService
 * @param string $user
 * @param Parser $parser
 * @param int $minDelay
 * @param array $allowedFrom
 * @param array $notAllowedFrom
 * @param array|bool $options
 * @param mixed $hiddenLabelId
 * @return void
 * @throws Exception
 */
function processMessage(GmailMessage $message, GmailService $gmailService, string $user, Parser $parser, int $minDelay, array $allowedFrom, array $notAllowedFrom, array|bool $options, mixed $hiddenLabelId): void
{
    if ($message = getMessage($gmailService, $user, $message)) {
        $parser->setText(decodeMessage($message));
        $from = $parser->getHeader('from');
        $sentOn = getSentDate($parser->getHeader('date'), $minDelay);

        echo date('Y-m-d H:i:s') . ' Came from: ' . $from . '. Date: ' . $sentOn->format('dd-MM-YYYY') . PHP_EOL;

        if (shouldMessageBeMovedToInbox($sentOn, $minDelay, $allowedFrom, $from, $notAllowedFrom)) {
            if (!isDryRun($options)) {
                moveToInbox($message, $gmailService, $user, $hiddenLabelId);
            }
        }
    }
}

/**
 * @param bool|array $options
 * @return bool
 */
function isDryRun(bool|array $options): bool
{
    return array_key_exists('dry-run', $options);
}

/**
 * @param DateTimeImmutable $sentOn
 * @param int $minDelay
 * @param array $allowedFrom
 * @param bool|string $from
 * @param array $notAllowedFrom
 * @return bool
 * @throws Exception
 */
function shouldMessageBeMovedToInbox(DateTimeImmutable $sentOn, int $minDelay, array $allowedFrom, bool|string $from, array $notAllowedFrom): bool
{
    return wasSentWithinAcceptableTimeFrame($sentOn, $minDelay) &&
        wasSentByAllowedSender($allowedFrom, $from) &&
        !senderBelongs($notAllowedFrom, $from);
}

/**
 * @param array $allowedFrom
 * @param bool|string $from
 * @return bool
 */
function wasSentByAllowedSender(array $allowedFrom, bool|string $from): bool
{
    return (empty($allowedFrom) || senderBelongs($allowedFrom, $from));
}

/**
 * @return array|false|false[]|string[]
 */
function getCLIOptions(): array|false
{
    return getopt('b:w:m:', ['dry-run']);
}
