<?php

namespace PriorityInbox\Command;

use Exception;
use Google\Client;
use Google\Exception as GoogleException;
use Google\Service\Gmail;
use Google\Client as GoogleClient;
use PhpMimeMailParser\Parser;
use PriorityInbox\EmailPriorityMover;
use PriorityInbox\Label;
use PriorityInbox\Providers\GmailDAO;
use PriorityInbox\Providers\GmailRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: "release-email")]
class ReleaseEmailCommand extends Command
{
    private EmailPriorityMover $emailPriorityMover;

    protected function configure(): void
    {
        $this
            ->setHelp("This command moves hidden emails into the inbox")
            ->addOption("white-list", "w", InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "Name of the file containing allowed senders")
            ->addOption("black-list", "b", InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "Name of the file containing not allowed senders")
            ->addOption("minimum-delay", "m", InputOption::VALUE_REQUIRED, "How many hours must have since emails were sent", 0)
            ->addArgument("hidden-label-id",  InputArgument::REQUIRED, "Id of the label used to hide emails")
            ->addArgument("application-name", InputArgument::REQUIRED, "Gmail application name" )
            ->addArgument("client-secret-path", InputArgument::REQUIRED, "Path to json file where Gmail client secrets are stored")
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws GoogleException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->writeln("Fetching emails in OOP way!");

        $this->setupEmailMover($input);
        $this->fillInbox();

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @return void
     * @throws GoogleException
     */
    private function setupEmailMover(InputInterface $input): void
    {
        $gmailDAO = new GmailDAO(new Gmail($this->buildGmailClient(
            $input->getArgument('application-name'),
            $input->getArgument('client-secret-path'),
            $input->getArgument('client-credentials-path')
        )));
        $gmailRepository = new GmailRepository($gmailDAO, new Parser());
        $this->emailPriorityMover = new EmailPriorityMover(
            $gmailRepository,
            new Label($input->getArgument('hidden-label-id'))
        );

        if ($minDelay = $input->getOption('minimum-delay')) {
            $this->emailPriorityMover->setMinimumDelay($minDelay);
        }

        if ($whiteList = $input->getOption('white-list')) {
            foreach ($whiteList as $whiteListFile) {
                if (is_readable($whiteListFile)) {
                    foreach (file($whiteListFile) as $allowedSender) {
                        $this->emailPriorityMover->addAllowedSender($allowedSender);
                    }
                } else {
                    $this->emailPriorityMover->addAllowedSender($whiteList);
                }
            }
        }

        if ($blackList = $input->getOption('black-list')) {
            foreach ($blackList as $blackListFile) {
                if (is_readable($blackListFile)) {
                    foreach (file($blackListFile) as $allowedSender) {
                        $this->emailPriorityMover->addNotAllowedSender($allowedSender);
                    }
                } else {
                    $this->emailPriorityMover->addNotAllowedSender($blackList);
                }
            }
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function fillInbox(): void
    {
        $this
            ->emailPriorityMover
            ->fillInbox();
    }

    /**
     * @param string $applicationName
     * @param string $clientSecretPath
     * @param string $credentialsPath
     * @return GoogleClient
     * @throws GoogleException
     */
    private function buildGmailClient(string $applicationName, string $clientSecretPath, string $credentialsPath) : Client
    {
        $client = new GoogleClient();
        $client->setApplicationName($applicationName);
        $client->setScopes([Gmail::MAIL_GOOGLE_COM]);
        $client->setAuthConfig($clientSecretPath);
        $client->setAccessType('offline');

        $this->loadExistingCredentialsIfPossible($credentialsPath);

        logMessage('Using credentials found at ' . $credentialsPath);

        $client->setAccessToken($this->getAccessTokenDataFrom($credentialsPath));

        $this->refreshTokenIfExpired($client, $credentialsPath);

        return $client;
    }

    /**
     * @param string $credentialsPath
     * @return void
     * @throws GoogleException
     */
    private function loadExistingCredentialsIfPossible(string $credentialsPath): void
    {
        if (!file_exists($credentialsPath)) {
            throw new GoogleException("Credentials not found. Run the authorize command first");
        }
    }

    /**
     * @param string $credentialsPath
     * @return mixed
     */
    private function getAccessTokenDataFrom(string $credentialsPath): mixed
    {
        return json_decode(file_get_contents($credentialsPath), true);
    }

    /**
     * @param string $credentialsPath
     * @param array $accessToken
     * @return void
     */
    private function storeAccessTokenData(string $credentialsPath, array $accessToken): void
    {
        file_put_contents($credentialsPath, json_encode($accessToken));
    }

    /**
     * @param GoogleClient $client
     * @param string $credentialsPath
     * @return void
     */
    private function refreshTokenIfExpired(GoogleClient $client, string $credentialsPath): void
    {
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $this->storeAccessTokenData($credentialsPath, $client->getAccessToken());
        }
    }
}