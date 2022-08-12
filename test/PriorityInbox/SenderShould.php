<?php


namespace PriorityInbox;

use PHPUnit\Framework\TestCase;

class SenderShould extends TestCase
{
    /**
     * @test
     * @param bool $expected
     * @param Sender $other
     * @dataProvider senderProvider
     */
    public function recognize_partial_matches(bool $expected, Sender $other): void
    {
        $sender = new Sender("mauro");
        $this->assertEquals($expected, $sender->matches($other));
    }

    /**
     * @return array[]
     */
    public function senderProvider(): array
    {
        return [
            [true, new Sender("mauro")],
            [false, new Sender("maria")],
            [true, new Sender("mauro.chojrin@leewayweb.com")],
            [false, new Sender("auro.chojrin@gmail.com")],
        ];
    }
}
