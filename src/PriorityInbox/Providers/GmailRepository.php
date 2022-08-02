<?php

namespace PriorityInbox\Providers;

use DateTimeImmutable;
use Google\Service\Gmail\Message;
use PriorityInbox\Email;
use PriorityInbox\EmailFilter;
use PriorityInbox\EmailRepository;
use PriorityInbox\EmailUpdate;
use PriorityInbox\Label;
use PriorityInbox\RemoveLabel;

class GmailRepository implements EmailRepository
{
    private GmailDAO $gmail;

    public function __construct(GmailDAO $gmail)
    {
        $this->gmail = $gmail;
    }

    /**
     * @param array<EmailFilter> $filters
     * @return array<Email>
     */
    public function fetch(array $filters = []): array
    {
        $emails = [];

        foreach ($this->getGmailMessages($filters) as $message) {
            $emails = $this->buildEmailFrom($message);
        }

        return $emails;
    }

    /**
     * @param Email $email
     * @return void
     */
    public function updateEmail(Email $email): void
    {
        $this
            ->gmail
            ->modifyMessage($email->id(), $this->buildUpdate($email));
    }



    /**
     * @param mixed $message
     * @return Email
     * @todo FIXME
     */
    private function buildEmailFrom(Message $message) : Email
    {
        return new Email(new DateTimeImmutable());
    }

    /**
     * @param array<EmailFilter> $filters
     * @return Message[]
     */
    private function getGmailMessages(array $filters = []): array
    {
        return $this
            ->gmail
            ->getFilteredMessageList($filters)
            ;
    }

    /**
     * @param Email $email
     * @return EmailUpdate
     */
    private function buildUpdate(Email $email): EmailUpdate
    {
        $emailUpdate = new EmailUpdate();

        foreach ($email->addedLabels() as $addedLabel) {
            $emailUpdate->addLabel($addedLabel);
        }

        foreach ($email->removedLabels() as $removedLabel ) {
            $emailUpdate->removeLabel($removedLabel);
        }

        return $emailUpdate;
    }

    /**
     * @param Email $email
     * @return array<RemoveLabel>
     */
    private function buildRemoveLabelUpdates(Email $email): array
    {
        return array_map(fn(Label $label) => new RemoveLabel($label), $email->removedLabels());
    }


}