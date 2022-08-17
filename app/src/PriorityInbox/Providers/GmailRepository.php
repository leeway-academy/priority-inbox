<?php

namespace PriorityInbox\Providers;

use DateTimeImmutable;
use Exception;
use Google\Service\Gmail\Message;
use PhpMimeMailParser\Parser;
use PriorityInbox\Email;
use PriorityInbox\EmailFilter;
use PriorityInbox\EmailId;
use PriorityInbox\EmailRepository;
use PriorityInbox\EmailUpdate;
use PriorityInbox\Label;
use PriorityInbox\Sender;

class GmailRepository implements EmailRepository
{
    private GmailDAO $gmail;

    private Parser $parser;

    /**
     * @param GmailDAO $gmail
     * @param Parser $parser
     */
    public function __construct(GmailDAO $gmail, Parser $parser)
    {
        $this->gmail = $gmail;
        $this->parser = $parser;
    }

    /**
     * @param array<EmailFilter> $filters
     * @return array<Email>
     */
    public function fetch(array $filters = []): array
    {
        $emails = [];

        foreach ($this->getGmailMessages($filters) as $message) {
            $email = $this->buildEmailFrom($message);
            $emails[] = $email;
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
            ->getGmail()
            ->modifyMessage($email->id(), $this->buildUpdate($email));
    }

    /**
     * @param mixed $message
     * @return Email
     */
    private function buildEmailFrom(Message $message): Email
    {
        $this
            ->parser()
            ->setText($this->decodeMessage($message));

        $email = new Email(
            $this->getEmailIdFrom($message),
            $this->getSenderFromParsedData(),
            $this->getSentAtFromParsedData(),
        );

        foreach ($message->getLabelIds() as $labelId) {
            $email->addLabel(new Label($labelId));
        }

        return $email;
    }


    /**
     * @param array<EmailFilter> $filters
     * @return Message[]
     */
    private function getGmailMessages(array $filters = []): array
    {
        return $this
            ->gmail
            ->getFilteredMessageList($filters);
    }

    /**
     * @param Email $email
     * @return EmailUpdate
     */
    private function buildUpdate(Email $email): EmailUpdate
    {
        $emailUpdate = new EmailUpdate();

        $emailUpdate
            ->setAddedLabels($email->addedLabels())
            ->setRemovedLabels($email->removedLabels())
        ;

        return $emailUpdate;
    }

    /**
     * @param Message $message
     * @return EmailId
     */
    private function getEmailIdFrom(Message $message): EmailId
    {
        return new EmailId($message->getId());
    }

    /**
     * @return Sender
     */
    private function getSenderFromParsedData(): Sender
    {
        return new Sender($this
            ->parser()
            ->getHeader('from'));
    }

    /**
     * @return DateTimeImmutable
     */
    private function getSentAtFromParsedData(): DateTimeImmutable
    {
        return $this->buildDateFrom($this->parser()->getHeader('date'));
    }

    /**
     * @param string $dateString
     * @return DateTimeImmutable
     * @todo Check the exception condition. It doesn't 100% match the original one (found in fetch.php)
     */
    private function buildDateFrom(string $dateString): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($dateString);
        } catch (Exception $exception) {
            return new DateTimeImmutable();
        }
    }

    /**
     * @return Parser
     */
    private function parser(): Parser
    {
        return $this->parser;
    }

    /**
     * @param Message $message
     * @return string
     */
    private function decodeMessage(Message $message): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $message->getRaw()));
    }

    /**
     * @return GmailDAO
     */
    protected function getGmail(): GmailDAO
    {
        return $this->gmail;
    }
}
