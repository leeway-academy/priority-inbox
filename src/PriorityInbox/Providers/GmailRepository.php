<?php

namespace PriorityInbox\Providers;

use DateTimeImmutable;
use PriorityInbox\Email;
use PriorityInbox\EmailFilter;
use PriorityInbox\EmailRepository;
use Google\Service\Gmail\Message;

class GmailRepository extends EmailRepository
{
    private GmailService $gmail;

    public function __construct(GmailService $gmail)
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
            ->modifyMessage($email->id());

        /**
         * logMessage('Moving to Inbox');
        $mods = new Google_Service_Gmail_ModifyMessageRequest();
        $mods->setAddLabelIds(['INBOX']);
        $mods->setRemoveLabelIds([$hiddenLabelId]);
        $service
        ->users_messages
        ->modify('me',
        $message->getId(),
        $mods);
        logMessage("Message with ID: {$message->getId()} successfully modified.");
         */
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
}