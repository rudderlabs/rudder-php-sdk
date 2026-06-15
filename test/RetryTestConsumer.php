<?php

declare(strict_types=1);

namespace Rudder\Test;

use Rudder\Consumer\QueueConsumer;

class RetryTestConsumer extends QueueConsumer
{
    public function flushBatch(array $batch): bool
    {
        return true;
    }

    public function retryableStatusCode(int $statusCode): bool
    {
        return $this->isRetryableStatusCode($statusCode);
    }

    /**
     * @param array<string,string> $headers
     */
    public function retryDelay(int $retryNumber, array $headers = []): int
    {
        return $this->retryDelayInMicroseconds($retryNumber, $headers);
    }

    public function canRetryStatus(int $statusCode, int $retries): bool
    {
        return $this->canRetryStatusCode($statusCode, $retries);
    }
}
