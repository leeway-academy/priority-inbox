<?php

namespace PriorityInbox\Providers;
use Google\Service\Gmail;

class GmailService extends Gmail
{
    public function listUsersMessages() : array
    {
        return [];
    }
}