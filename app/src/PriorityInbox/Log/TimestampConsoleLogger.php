<?php

namespace PriorityInbox\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class TimestampConsoleLogger extends AbstractLogger implements LoggerInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log($level, $message, array $context = []): void
    {
        $timestamp = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $message = "[$timestamp] $message";
        
        $this->logger->log($level, $message, $context);
    }
}