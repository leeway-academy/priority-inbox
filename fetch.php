#!/usr/bin/php -q

<?php
require_once __DIR__ . '/vendor/autoload.php';

$options = getopt('iuas:', [ 'dry-run' ]);

define('APPLICATION_NAME', 'Gmail API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__.'/credentials.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/gmail-php-quickstart.json
define('SCOPES', implode(' ', array(
        Google_Service_Gmail::MAIL_GOOGLE_COM)
));

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

echo 'Using credentials from: '.CREDENTIALS_PATH.PHP_EOL;
/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
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


// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Gmail($client);

// Print the labels in the user's account.
$user = 'me';

try {
    $labelsResponse = $service->users_labels->listUsersLabels($user);

    if ( $labels = $labelsResponse->getLabels() ) {
      foreach ($labels as $label) {
	if ( substr( $label->getName(), 0, strlen('Inbox-Paused') ) == 'Inbox-Paused' ) {
		$inboxPausedId = trim($label->getId());
		$inboxPausedName = $label->getName();
		break;
	}
     }
  }
} catch (Excetion $e) {
    die( 'An error occurred: ' . $e->getMessage() );
}

echo "Fetching messages labeled '$inboxPausedName', id: '$inboxPausedId'".PHP_EOL;
$results = $service->users_messages->listUsersMessages($user, [ 'labelIds' => [ $inboxPausedId ] ]);

$messages = $results->getMessages();

$allowedFrom = array_key_exists('s', $options) ? [ $options['s'] ] : [];
$allowedFrom = array_merge( $allowedFrom, array_key_exists( 'i', $options ) ? include __DIR__.'/important_senders.php' : [] );
$allowedFrom = array_merge( $allowedFrom, array_key_exists( 'u', $options ) ? include __DIR__.'/urgent_senders.php' : [] );

$parser = new PhpMimeMailParser\Parser();

if ( array_key_exists('s', $options) ) {
	$urgentSenders[] = $options['s'];
}

foreach ( $messages as $message ) {
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
		
		if ( array_key_exists( 'a', $options ) || senderBelongs( $from, $allowedFrom ) ) {
			echo 'Came from: '.$from.' moving to INBOX'.PHP_EOL;
			if ( !array_key_exists('dry-run', $options) ) {
				moveToInbox( $service, $user, $message, $inboxPausedId );
			}
		}
	}
}

function moveToInbox( Google_Service_Gmail $service, string $user, $message, string $inboxPausedId )
{
	$mods = new Google_Service_Gmail_ModifyMessageRequest();
	$mods->setAddLabelIds(['INBOX']);
	$mods->setRemoveLabelIds( [$inboxPausedId] );
	try {
	    $message = $service->users_messages->modify($user, $message->getId(), $mods);
	    print 'Message with ID: ' . $message->getId() . ' successfully modified.'.PHP_EOL;
	} catch (Exception $e) {
	    print 'An error occurred: ' . $e->getMessage().PHP_EOL;
	}
}

function senderBelongs( string $from, array $importantSenders ) : bool
{
	foreach ( $importantSenders as $importantSender ) {
		if ( strpos( $from, $importantSender ) !== false ) {
	
			return true;
		}
	}

	return false;
}
