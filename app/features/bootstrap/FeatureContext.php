<?php

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Hook\BeforeFeature;
use PriorityInbox\{Command\ReleaseEmailCommand, Email, EmailId, Label, Sender};
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use function PHPUnit\Framework\assertContainsEquals;
use function PHPUnit\Framework\assertNotContainsEquals;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    private EmailRepositoryStub $emailRepository;
    private string $hiddenLabelId;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->emailRepository = $this->buildEmailRepository();
    }

    /**
     * @Given The hidden labelId is :hiddenLabelId
     */
    public function theHiddenLabelidIs(string $hiddenLabelId)
    {
        $this->hiddenLabelId = $hiddenLabelId;
    }

    /**
     * @Given There is an email with id :emailId from :sender labeled :labelId
     */
    public function thereIsAnEmailWithIdFromLabeled(string $emailId, string $sender, string $labelId)
    {
        $email = new Email(new EmailId($emailId), new Sender($sender), new DateTimeImmutable());
        $email->addLabel(new Label($labelId));
        $this->emailRepository->addEmail($email);
    }

    /**
     * @When I run the command run.php :invocationArguments
     * @throws ExceptionInterface
     */
    public function iRunTheCommandRunPhp(string $invocationArguments)
    {
        $theCommand = new ReleaseEmailCommand($this->emailRepository);
        $theCommand->addOption('verbose', 'v');
        $output = new BufferedOutput();
        $invocationArguments = $this->hiddenLabelId . " " . $invocationArguments;
        $theCommand->run(new StringInput($invocationArguments), $output);
    }

    /**
     * @Then the email with id :emailId should be labeled :labelId
     */
    public function theEmailWithIdShouldBeLabeled(string $emailId, string $labelId)
    {
        assertContainsEquals(new Label($labelId), $this
            ->emailRepository
            ->getEmail($emailId)
            ->labels());
    }

    /**
     * @Then the email with id :emailId should not be labeled :labelId
     */
    public function theEmailWithIdShouldNotBeLabeled(string $emailId, string $labelId)
    {
        assertNotContainsEquals(new Label($labelId), $this
            ->emailRepository
            ->getEmail($emailId)
            ->labels());
    }

    /**
     * @return EmailRepositoryStub
     */
    private function buildEmailRepository(): EmailRepositoryStub
    {
        return new EmailRepositoryStub();
    }

    /**
     * @Given /^The file "([^"]*)" contains "([^"]*)"$/
     */
    public function theFileContains(string $filename, string $text)
    {
        if (is_readable($filename)) {
            $contents = file($filename);

            if (!in_array($text.PHP_EOL, $contents)) {
                $contents[] = $text.PHP_EOL;
            }
        } else {
            $contents = [$text.PHP_EOL];
        }

        file_put_contents($filename, $contents);
    }

    /**
     * @BeforeScenario
     */
    public function initEmailRepository()
    {
        $this->emailRepository->setEmails([]);
    }
}
