<?php

namespace PriorityInbox\Providers;

use PriorityInbox\Email;
use PriorityInbox\EmailRepository;
use Google\Service\Gmail as GmailService;

class GmailRepository extends EmailRepository
{
    public function __construct(GmailService $gmail)
    {

    }

    public function fetch(): array
    {
        return [];
    }

    public function updateEmail(Email $param): void
    {
        // TODO: Implement updateEmail() method.
    }
}