<?php

namespace PriorityInbox;

class EmailId
{
    private string $value;
    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}