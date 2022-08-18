<?php

namespace PriorityInbox;

class SenderPattern
{
    private string $value;

    /**
     * @param String $value
     */
    public function __construct(String $value)
    {
        $this->value = $value;
    }

    /**
     * @param Sender $sender
     * @return bool
     */
    public function matches(Sender $sender): bool
    {
        return str_contains($sender->value(), $this->value);
    }
}
