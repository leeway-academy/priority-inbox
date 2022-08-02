<?php

namespace PriorityInbox;

class Label
{
    private string $value;
    private string $id;

    /**
     * @param string $id
     * @param string $value
     */
    public function __construct(string $id, string $value)
    {
        $this->value = $value;
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }
}