<?php

namespace PriorityInbox;

/**
 * @todo build some more abstract query methods
 */
abstract class EmailRepository
{
    private array $filters = [];

    public abstract function fetch(): array;

    public abstract function updateEmail(Email $param): void;

    public function addFilter(LabelFilter $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }
}