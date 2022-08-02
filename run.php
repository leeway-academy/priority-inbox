#!/usr/bin/env php

<?php

require __DIR__ . '/vendor/autoload.php';

use PriorityInbox\Command\ReleaseEmailCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

if (php_sapi_name() != 'cli') {
    die('This application must be run on the command line.');
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$application = new Application();

$releaseEmailCommand = new ReleaseEmailCommand();

$commandRunner = new CommandTester($releaseEmailCommand);

try {
    $commandRunner->execute([
        "hidden-label-id" => $_ENV['HIDDEN_LABEL_ID'],
        "application-name" => $_ENV['APPLICATION_NAME'],
        "client-secret-path" => $_ENV['CLIENT_SECRET_PATH'],
        "client-credentials-path" => $_ENV['CLIENT_CREDENTIALS_PATH'],
    ]);
} catch (Exception $e) {
    die($e->getMessage());
}