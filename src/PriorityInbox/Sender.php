<?php

namespace PriorityInbox;

class Sender
{
    private string $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @param Sender $other
     * @return bool
     */
    public function matches(Sender $other) : bool
    {
        return str_contains($other->value, $this->value );
    }
}