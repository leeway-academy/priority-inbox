<?php

namespace PriorityInbox;

abstract class EmailFilter
{
    abstract public function getExpression() : array;
}