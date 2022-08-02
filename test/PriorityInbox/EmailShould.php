<?php

namespace PriorityInbox;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class EmailShould extends TestCase
{
    /**
     * @test
     */
    public function keep_track_of_added_labels(): void
    {
        $email = $this->buildEmail();
        $newLabel = new Label("added", "label");

        $email->addLabel($newLabel);
        $this->assertEquals([$newLabel], $email->addedLabels());
    }

    /**
     * @test
     */
    public function keep_track_of_removed_labels(): void
    {
        $email = $this->buildEmail();
        $newLabel = new Label("added", "label");

        $email->addLabel($newLabel);
        $email->removeLabel($newLabel);

        $this->assertEquals([$newLabel], $email->removedLabels());
    }

    /**
     * @return Email
     */
    private function buildEmail(): Email
    {
        return new Email(new EmailId("1"), new Sender("sender"), new DateTimeImmutable());
    }
}