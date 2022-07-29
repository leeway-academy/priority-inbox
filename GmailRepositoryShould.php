<?php

namespace PriorityInbox\Providers;

use PHPUnit\Framework\TestCase;

class GmailRepositoryShould extends TestCase
{
    private GmailRepository $gmailRepository;
    private GmailService $gmailService;

    /**
     * @test
     */
    public function fetch_emails_from_gmail(): void
    {
        $this->gmailService
            ->expects($this->once())
            ->method('listUsersMessages')
        ;

        $this->gmailRepository->fetch();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->gmailService = $this->createMock(GmailService::class);
        $this->gmailRepository = new GmailRepository($this->gmailService);
    }
}
