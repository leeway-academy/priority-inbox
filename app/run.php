#!/usr/bin/env php

<?php

require __DIR__ . '/vendor/autoload.php';

use PriorityInbox\Command\ReleaseEmailCommand;
use Symfony\Component\Console\Application;

if (php_sapi_name() != 'cli') {
    die('This application must be run on the command line.');
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$application = new Application();
$command = new ReleaseEmailCommand();

$application->add($command);
$application->setDefaultCommand($command->getName(), true);

$_SERVER['argv'][] = $_ENV['HIDDEN_LABEL_ID'];
$_SERVER['argv'][] = $_ENV['APPLICATION_NAME'];
$_SERVER['argv'][] = $_ENV['CLIENT_SECRET_PATH'];
$_SERVER['argv'][] = $_ENV['CLIENT_CREDENTIALS_PATH'];

try {
    $application->run();
} catch (Exception $e) {
    die($e->getMessage());
}
