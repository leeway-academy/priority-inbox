<?php

namespace PriorityInbox;

/**
 * @todo build some more abstract query methods
 */
abstract class EmailRepository
{
    /**
     * @param array $filters
     * @return array<Email>
     */
    public abstract function fetch(array $filters): array;

    /**
     * @param Email $email
     * @return void
     */
    public abstract function updateEmail(Email $email): void;
}