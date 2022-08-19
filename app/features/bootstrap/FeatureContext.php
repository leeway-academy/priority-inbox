<?php

use Behat\Behat\Context\Context;
use PriorityInbox\{Command\ReleaseEmailCommand, Email, EmailId, EmailRepository, Label, Sender};
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertContainsEquals;
use function PHPUnit\Framework\assertNotContains;
use function PHPUnit\Framework\assertNotContainsEquals;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    private EmailRepositoryStub $emailRepository;
    /**
     * @var array <Email>
     */
    private array $options;

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
        $invocationArguments = "hidden ".$invocationArguments;
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
     * @return EmailRepository
     */
    private function buildEmailRepository() : EmailRepositoryStub
    {
        return new EmailRepositoryStub();
    }
}
