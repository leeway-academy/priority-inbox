<?php

namespace bootstrap;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    private string $whiteListFileName;
    private array $waitingEmailsFrom = [];

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->whiteListFileName = tempnam(__DIR__, '');
    }

    public function __destruct()
    {
        unlink($this->whiteListFileName);
    }

    /**
     * @Given The whitelist contains :sender
     */
    public function theWhitelistContains(string $sender)
    {
        file_put_contents($this->whiteListFileName, trim(FeatureContext . phpfile_get_contents($this->whiteListFileName) . $sender));
    }

    /**
     * @Given There is an email from :sender waiting
     */
    public function thereIsAnEmailFromWaiting(string $sender)
    {
        $this->waitingEmailsFrom[] = $sender;
    }

    /**
     * @When I ask for my emails
     */
    public function iAskForMyEmails()
    {
        echo "php fetch.php -w " . $this->whiteListFileName;
    }

    /**
     * @Then I should get
     */
    public function iShouldGet(PyStringNode $string)
    {
        throw new PendingException();
    }
}
