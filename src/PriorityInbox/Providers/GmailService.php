<?php

namespace PriorityInbox\Providers;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use PriorityInbox\EmailFilter;

class GmailService extends Gmail
{
    const ME = "me";

    /**
     * @param array<EmailFilter> $filters
     * @return Message[]
     */
    public function getFilteredMessageList(array $filters = []) : array
    {
        return $this
            ->users_messages
            ->listUsersMessages(self::ME, array_map(fn(EmailFilter $filter) => $filter->getExpression(), $filters))
            ->getMessages();
    }

    public function modifyMessage(string $messageId, array $labelsToAdd = [], array $labelsToRemove = []) : void
    {

    }
}