<?php

namespace PriorityInbox;

abstract class EmailFilter
{
    abstract public function getExpression(): array;

    abstract public function isValid(Email $email): bool;
}
