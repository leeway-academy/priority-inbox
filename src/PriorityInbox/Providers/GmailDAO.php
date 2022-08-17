<?php

namespace PriorityInbox\Providers;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\ModifyMessageRequest;
use Google\Service\Gmail\Resource\UsersMessages;
use PriorityInbox\EmailFilter;
use PriorityInbox\EmailUpdate;

class GmailDAO
{
    const GMAIL_USER = "me";
    const FORMAT_KEY = 'format';
    const FORMAT_RAW = 'raw';
    private Gmail $gmail;

    /**
     * @param Gmail $gmail
     */
    public function __construct(Gmail $gmail)
    {
        $this->gmail = $gmail;
    }

    /**
     * @param array<EmailFilter> $filters
     * @return Message[]
     */
    public function getFilteredMessageList(array $filters = []) : array
    {
        $retrievedMessages = $this
            ->getUserMessages()
            ->listUsersMessages(self::GMAIL_USER, current(array_map(fn(EmailFilter $filter) => $filter->getExpression(), $filters)))
            ->getMessages();

        $return = [];

        foreach ($retrievedMessages as $retrievedMessage) {
            $return[] = $this
                ->getUserMessages()
                ->get( self::GMAIL_USER, $retrievedMessage->getId(), [ self::FORMAT_KEY => self::FORMAT_RAW] );
        }

        return $return;
    }

    /**
     * @param string $messageId
     * @param EmailUpdate $update
     * @return void
     */
    public function modifyMessage(string $messageId, EmailUpdate $update) : void
    {
        $this->executeModification($messageId, $this->buildModifyRequest($update));
    }

    /**
     * @return UsersMessages
     */
    protected function getUserMessages(): UsersMessages
    {
        return $this->gmail->users_messages;
    }

    /**
     * @param string $messageId
     * @param ModifyMessageRequest $request
     * @return void
     */
    private function executeModification(string $messageId, ModifyMessageRequest $request): void
    {
        $this->getUserMessages()
            ->modify(self::GMAIL_USER,
                $messageId,
                $request);
    }

    /**
     * @param EmailUpdate $update
     * @return ModifyMessageRequest
     */
    private function buildModifyRequest(EmailUpdate $update): ModifyMessageRequest
    {
        $modifyMessageRequest = new ModifyMessageRequest();

        $modifyMessageRequest->setAddLabelIds($update->addLabelIds());
        $modifyMessageRequest->setRemoveLabelIds($update->removeLabelIds());

        return $modifyMessageRequest;
    }
}