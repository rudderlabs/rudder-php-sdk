<?php

declare(strict_types=1);

namespace Rudder\Test;

use PHPUnit\Framework\TestCase;

class ConsumerSocketRetryTest extends TestCase
{
    public function testRetriesRateLimitUntilSuccess(): void
    {
        $consumer = new RetrySocketConsumer('secret');
        $consumer->queueResponse(429, [], 'Rate limited');
        $consumer->queueResponse(200, [], 'OK');

        self::assertTrue($consumer->flushBatch([self::message()]));
        self::assertSame(2, $consumer->createdSockets());
    }

    public function testReturnsFalseAfterRetryBudgetIsExhausted(): void
    {
        $consumer = new RetrySocketConsumer('secret');
        $consumer->queueResponse(429, [], 'Rate limited');
        $consumer->queueResponse(429, [], 'Still rate limited');

        self::assertFalse($consumer->flushBatch([self::message()]));
        self::assertSame(2, $consumer->createdSockets());
    }

    public function testDoesNotRetryTerminalClientErrors(): void
    {
        $consumer = new RetrySocketConsumer('secret', [
            'max_retries' => 3,
        ]);
        $consumer->queueResponse(400, [], 'Bad request');

        self::assertFalse($consumer->flushBatch([self::message()]));
        self::assertSame(1, $consumer->createdSockets());
    }

    public function testReportsTerminalErrorsWithoutDebug(): void
    {
        $reportedErrors = [];
        $consumer = new RetrySocketConsumer('secret', [
            'debug' => false,
            'max_retries' => 0,
            'error_handler' => static function (int $code, string $message) use (&$reportedErrors): void {
                $reportedErrors[] = [$code, $message];
            },
        ]);
        $consumer->queueResponse(400, [], 'Bad request');

        self::assertFalse($consumer->flushBatch([self::message()]));
        self::assertSame([[400, 'Bad Request']], $reportedErrors);
    }

    public function testHonorsRetryAfterOnRateLimit(): void
    {
        $consumer = new RetrySocketConsumer('secret', [
            'max_retries' => 1,
        ]);
        $consumer->queueResponse(429, ['Retry-After' => '1'], 'Rate limited');
        $consumer->queueResponse(200, [], 'OK');

        $start = microtime(true);
        self::assertTrue($consumer->flushBatch([self::message()]));
        self::assertGreaterThanOrEqual(1.0, microtime(true) - $start);
        self::assertSame(2, $consumer->createdSockets());
    }

    /**
     * @return array<string,mixed>
     */
    private static function message(): array
    {
        return [
            'type' => 'track',
            'userId' => 'socket-retry-user',
            'event' => 'Socket Retry PHP Event',
            'properties' => (object)[],
            'timestamp' => date('c'),
            'messageId' => 'socket-retry-message-id',
            'channel' => 'server',
            'context' => [
                'library' => [
                    'name' => 'rudder-analytics-php',
                    'version' => 'test',
                    'consumer' => 'socket',
                ],
            ],
        ];
    }
}
