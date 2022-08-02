<?php

namespace PriorityInbox\Providers;

use Google\Service\Gmail;
use PHPUnit\Framework\TestCase;

class GmailDAOShould extends TestCase
{
    /**
     * @test
     */
    public function get_messages_from_gmail(): void
    {
        $this->markTestIncomplete("This test requires some Gmail refactoring, not needed for the time being");
    }

    /**
     * @test
     */
    public function update_messages_in_gmail(): void
    {
        $this->markTestSkipped("This test requires some Gmail refactoring, not needed for the time being");
    }
}
