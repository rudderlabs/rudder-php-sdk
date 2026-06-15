<?php

declare(strict_types=1);

namespace Rudder\Test;

use PHPUnit\Framework\TestCase;

class ConsumerRetryTest extends TestCase
{
    public function testRetryableStatusCodes(): void
    {
        $consumer = new RetryTestConsumer('secret');

        self::assertTrue($consumer->retryableStatusCode(0));
        self::assertTrue($consumer->retryableStatusCode(429));
        self::assertTrue($consumer->retryableStatusCode(500));
        self::assertTrue($consumer->retryableStatusCode(599));
        self::assertFalse($consumer->retryableStatusCode(400));
        self::assertFalse($consumer->retryableStatusCode(404));
    }

    public function testRetryAfterIsFloorOnBackoff(): void
    {
        $consumer = new RetryTestConsumer('secret', [
            'retry_base_delay' => 100,
            'retry_jitter_ratio' => 0,
        ]);

        self::assertSame(
            2_000_000,
            $consumer->retryDelay(1, ['Retry-After' => '2'])
        );
    }

    public function testFirstRetryUsesBaseDelay(): void
    {
        $consumer = new RetryTestConsumer('secret', [
            'retry_base_delay' => 100,
            'retry_jitter_ratio' => 0,
        ]);

        self::assertSame(100_000, $consumer->retryDelay(1));
        self::assertSame(200_000, $consumer->retryDelay(2));
        self::assertSame(400_000, $consumer->retryDelay(3));
    }

    public function testRetryAfterDoesNotShortenBackoff(): void
    {
        $consumer = new RetryTestConsumer('secret', [
            'retry_base_delay' => 1000,
            'retry_jitter_ratio' => 0,
        ]);

        self::assertSame(
            1_000_000,
            $consumer->retryDelay(1, ['Retry-After' => '1'])
        );
    }

    public function testRetryBudget(): void
    {
        $consumer = new RetryTestConsumer('secret', [
            'max_retries' => 1,
        ]);

        self::assertTrue($consumer->canRetryStatus(429, 0));
        self::assertFalse($consumer->canRetryStatus(429, 1));
    }

    public function testTerminalStatusCodesAreNotRetryable(): void
    {
        $consumer = new RetryTestConsumer('secret');

        self::assertFalse($consumer->canRetryStatus(400, 0));
    }

    public function testRetryAfterHttpDateIsHonored(): void
    {
        $consumer = new RetryTestConsumer('secret', [
            'retry_base_delay' => 0,
            'retry_jitter_ratio' => 0,
        ]);
        $retryAt = gmdate('D, d M Y H:i:s \G\M\T', time() + 2);

        self::assertGreaterThanOrEqual(
            1_000_000,
            $consumer->retryDelay(1, ['Retry-After' => $retryAt])
        );
    }

    public function testRetryAfterCanBeDisabled(): void
    {
        $consumer = new RetryTestConsumer('secret', [
            'retry_base_delay' => 100,
            'retry_jitter_ratio' => 0,
            'respect_retry_after' => false,
        ]);

        self::assertSame(100_000, $consumer->retryDelay(1, ['Retry-After' => '2']));
    }
}
