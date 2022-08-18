<?php

namespace PriorityInbox;

use PHPUnit\Framework\TestCase;

class SenderPatternShould extends TestCase
{
    /**
     * @test
     */
    public function recognize_full_matches(): void
    {
        $senderPattern = new SenderPattern("user1@domain.com");
        $sender = new Sender("user2@domain.com");
        $this->assertFalse($senderPattern->matches($sender));

        $senderPattern = new SenderPattern("user1@domain.com");
        $sender = new Sender("user1@domain.com");
        $this->assertTrue($senderPattern->matches($sender));
    }

    /**
     * @test
     * @param bool $expected
     * @param SenderPattern $pattern
     * @param Sender $sender
     * @dataProvider senderProvider
     */
    public function recognize_partial_matches(bool $expected, SenderPattern $pattern, Sender $sender): void
    {
        $this->assertEquals($expected, $pattern->matches($sender));
    }

    /**
     * @return array[]
     */
    public function senderProvider(): array
    {
        return [
            [true, new SenderPattern("user1"), new Sender("user1@domain.com")],
            [true, new SenderPattern("@domain.com"), new Sender("user1@domain.com")],
            [true, new SenderPattern("User1"), new Sender("User1 Lastname <user1@domain.com>")],
            [false, new SenderPattern("user1"), new Sender("user2@domain.com")],
        ];
    }
}
