<?php

namespace PriorityInbox;

class EmailPriorityMover
{
    private EmailRepository $emailRepository;

    public function __construct(EmailRepository $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    public function fillInbox(): void
    {
        $this->emailRepository->updateEmail(new Email(new EmailId("1"), new EmailAddress("mchojrin@gmail.com")));
    }
}