<?php

namespace PriorityInbox\Providers;

use Google\Service\Gmail;
use Google\Service\Gmail\Resource\UsersMessages;
use PHPUnit\Framework\TestCase;

class TestableGmailDAO extends GmailDAO
{
    private UsersMessages $userMessages;

    /**
     * @param Gmail $gmail
     * @param UsersMessages $usersMessages
     */
    public function __construct(Gmail $gmail, UsersMessages $usersMessages)
    {
        parent::__construct($gmail);
        $this->userMessages = $usersMessages;
    }

    protected function getUserMessages(): UsersMessages
    {
        return $this->userMessages;
    }
}

class GmailDAOShould extends TestCase
{
    public const GOOGLE_USER = 'me';
    public const FORMAT_RAW = 'raw';
    public const FORMAT_KEY = 'format';

    /**
     * @test
     * @todo Revisit this test, seems too fragile
     */
    public function get_full_messages_from_gmail(): void
    {
        $listMessagesResponse = $this->createMock(Gmail\ListMessagesResponse::class);
        $message = new Gmail\Message();
        $message->setId('1');
        $raw = base64_encode("This is the email");
        $message->setRaw($raw);

        $messages = [$message];

        $listMessagesResponse
            ->expects($this->once())
            ->method('getMessages')
            ->willReturn($messages)
        ;

        $userMessages = $this->createMock(UsersMessages::class);
        $userMessages
            ->expects($this->once())
            ->method('listUsersMessages')
            ->willReturn($listMessagesResponse)
        ;

        $userMessages
            ->expects($this->exactly(count($messages)))
            ->method('get')
            ->with(self::GOOGLE_USER, $message->getId(), [ self::FORMAT_KEY => self::FORMAT_RAW, ])
        ;

        $userMessages
            ->method('get')
            ->with(self::GOOGLE_USER, $message->getId(), [ self::FORMAT_KEY => self::FORMAT_RAW, ])
            ->willReturn($raw)
        ;

        $gmailDAO = new TestableGmailDAO(new Gmail(), $userMessages);
        $retreivedMessages = $gmailDAO->getFilteredMessageList();

        $this->assertSameSize($retreivedMessages, $messages);
    }

    /**
     * @test
     */
    public function update_messages_in_gmail(): void
    {
        $this->markTestSkipped("This test requires some Gmail refactoring, not needed for the time being");
    }
}
