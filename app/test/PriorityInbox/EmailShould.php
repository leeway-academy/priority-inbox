<?php

namespace PriorityInbox;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class EmailShould extends TestCase
{
    const LABEL_ID = "added";

    /**
     * @test
     */
    public function keep_track_of_added_labels(): void
    {
        $email = $this->buildEmail();
        $newLabel = new Label(self::LABEL_ID);

        $email->addLabel($newLabel);
        $this->assertEquals([self::LABEL_ID => $newLabel], $email->addedLabels());
    }

    /**
     * @test
     */
    public function keep_track_of_removed_labels(): void
    {
        $email = $this->buildEmail();
        $newLabel = new Label(self::LABEL_ID);

        $email->addLabel($newLabel);
        $email->removeLabel($newLabel);

        $this->assertEquals([self::LABEL_ID => $newLabel], $email->removedLabels());
    }

    /**
     * @return Email
     */
    private function buildEmail(): Email
    {
        return new Email(new EmailId("1"), new Sender("sender"), new DateTimeImmutable());
    }
}