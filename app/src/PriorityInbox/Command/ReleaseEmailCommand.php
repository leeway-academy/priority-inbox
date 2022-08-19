<?php

namespace PriorityInbox\Command;

use Exception;
use PriorityInbox\EmailPriorityMover;
use PriorityInbox\EmailRepository;
use PriorityInbox\Label;
use PriorityInbox\SenderPattern;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: "release-email")]
class ReleaseEmailCommand extends Command
{
    private EmailPriorityMover $emailPriorityMover;
    private NullLogger|ConsoleLogger $logger;
    private EmailRepository $emailRepository;

    public function __construct(EmailRepository $emailRepository, string $name = null)
    {
        $this->emailRepository = $emailRepository;
        parent::__construct($name);
    }

    /**
     * @return EmailRepository
     */
    protected function getEmailRepository(): EmailRepository
    {
        return $this->emailRepository;
    }

    protected function configure(): void
    {
        $this
            ->setHelp("This command moves hidden emails into the inbox")
            ->addOption("white-list", "w", InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "Name of the file containing allowed senders")
            ->addOption("black-list", "b", InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "Name of the file containing not allowed senders")
            ->addOption("minimum-delay", "m", InputOption::VALUE_REQUIRED, "How many hours must have since emails were sent", 0)
            ->addOption("dry-run", "d", InputOption::VALUE_NONE, "Simulate move")
            ->addArgument("hidden-label-id", InputArgument::REQUIRED, "Id of the label used to hide emails")
//            ->addArgument("application-name", InputArgument::REQUIRED, "Gmail application name")
//            ->addArgument("client-secret-path", InputArgument::REQUIRED, "Path to json file where Gmail client secrets are stored")
//            ->addArgument("client-credentials-path", InputArgument::REQUIRED, "Path to where the access token is to be stored")
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupLogger($input->getOption('verbose'), $output);
        $this->getLogger()->info("Fetching emails in OOP way!");
        $this->setupEmailMover($input);
        $this->fillInbox();

        return Command::SUCCESS;
    }

    /**
     * @param EmailPriorityMover $emailPriorityMover
     * @return void
     */
    protected function setEmailPriorityMover(EmailPriorityMover $emailPriorityMover): void
    {
        $this->emailPriorityMover = $emailPriorityMover;
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    private function setupEmailMover(InputInterface $input): void
    {
        $this->setEmailPriorityMover($this->buildEmailPriorityMover($input));
        $this->setupMinimumDelay($input);
        $this->setupWhiteList($input);
        $this->setupBlackList($input);
        $this->setupDryRunMode($input);
    }

    /**
     * @return void
     * @throws Exception
     */
    private function fillInbox(): void
    {
        $this
            ->getLogger()
            ->info("Starting email moving");
        $this
            ->getEmailPriorityMover()
            ->fillInbox();
    }

    /**
     * @param array<string> $blackList
     * @return void
     */
    private function addNotAllowedSenderPatternsFromArray(array $blackList): void
    {
        foreach ($blackList as $blackListFile) {
            $this->addNotAllowedSenderPatternsFrom($blackListFile);
        }
    }

    /**
     * @param string $pattern
     * @return void
     */
    private function addNotAllowedSenderPattern(string $pattern): void
    {
        $this->getEmailPriorityMover()
            ->addNotAllowedSenderPattern(new SenderPattern($pattern));
    }

    /**
     * @param string $blackListFileName
     * @return void
     */
    private function addNotAllowedSenderPatternsFromFile(string $blackListFileName): void
    {
        foreach (file($blackListFileName) as $notAllowedSender) {
            $this->addNotAllowedSenderPattern($notAllowedSender);
        }
    }

    /**
     * @param string $blackListFile
     * @return void
     */
    private function addNotAllowedSenderPatternsFrom(string $blackListFile): void
    {
        if (is_readable($blackListFile)) {
            $this->addNotAllowedSenderPatternsFromFile($blackListFile);
        } else {
            $this->addNotAllowedSenderPattern($blackListFile);
        }
    }

    /**
     * @param array<string> $whiteList
     * @return void
     */
    private function addAllowedSenderPatternsFromArray(array $whiteList): void
    {
        foreach ($whiteList as $whiteListFile) {
            $this->addAllowedSenderPatternsFrom($whiteListFile);
        }
    }

    /**
     * @param string $pattern
     * @return void
     */
    private function addAllowedSenderPattern(string $pattern): void
    {
        $this
            ->getLogger()
            ->info('Allowing senders matching "' . $pattern . '"');
        $this
            ->getEmailPriorityMover()
            ->addAllowedSenderPattern(new SenderPattern($pattern));
    }

    /**
     * @param string $whiteListFileName
     * @return void
     */
    private function addAllowedSenderPatternsFromFile(string $whiteListFileName): void
    {
        foreach (file($whiteListFileName) as $pattern) {
            $this->addAllowedSenderPattern(trim($pattern));
        }
    }

    /**
     * @param string $whiteListFileName
     * @return void
     */
    private function addAllowedSenderPatternsFrom(string $whiteListFileName): void
    {
        if (is_readable($whiteListFileName)) {
            $this->addAllowedSenderPatternsFromFile($whiteListFileName);
        } else {
            $this->addAllowedSenderPattern($whiteListFileName);
        }
    }

    /**
     * @param bool $verbose
     * @param OutputInterface $output
     * @return void
     */
    private function setupLogger(bool $verbose, OutputInterface $output): void
    {
        $this->logger = $verbose ? new ConsoleLogger($output) : new NullLogger();
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return EmailPriorityMover
     */
    private function getEmailPriorityMover(): EmailPriorityMover
    {
        return $this->emailPriorityMover;
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    private function setupDryRunMode(InputInterface $input): void
    {
        if ($input->getOption('dry-run')) {
            $this
                ->getLogger()
                ->info("Running in dry mode");
            $this
                ->getEmailPriorityMover()
                ->setDryRun(true);
        }
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    private function setupBlackList(InputInterface $input): void
    {
        if ($blackList = $input->getOption('black-list')) {
            $this
                ->getLogger()
                ->info("Rejecting emails from: " . implode(", ", $blackList));
            if (is_array($blackList)) {
                $this->addNotAllowedSenderPatternsFromArray($blackList);
            } else {
                $this->addNotAllowedSenderPattern($blackList);
            }
        }
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    private function setupWhiteList(InputInterface $input): void
    {
        if ($whiteList = $input->getOption('white-list')) {
            $this
                ->getLogger()
                ->info("Allowing emails from: " . implode(", ", $whiteList));
            if (is_array($whiteList)) {
                $this->addAllowedSenderPatternsFromArray($whiteList);
            } else {
                $this->addAllowedSenderPattern($whiteList);
            }
        }
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    private function setupMinimumDelay(InputInterface $input): void
    {
        if ($minDelay = $input->getOption('minimum-delay')) {
            $this
                ->getEmailPriorityMover()
                ->setMinimumDelay($minDelay);
            $this
                ->getLogger()
                ->info("Minimum delay: " . $minDelay);
        }
    }

    /**
     * @param InputInterface $input
     * @return EmailPriorityMover
     */
    private function buildEmailPriorityMover(InputInterface $input): EmailPriorityMover
    {
        return new EmailPriorityMover(
            $this->getEmailRepository(),
            new Label($input->getArgument('hidden-label-id')),
            $this->getLogger()
        );
    }
}
