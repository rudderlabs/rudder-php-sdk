<?php

declare(strict_types=1);

namespace Rudder\Test;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Rudder\Client;
use Rudder\Consumer\QueueConsumer;

class QueueConsumerSizeOptionsTest extends TestCase
{
    public function testDefaultsMatchDocumentedValues(): void
    {
        $consumer = $this->createConsumer(['flush_at' => 10]);

        self::assertSame(32000, $this->readProtectedInt($consumer, 'max_item_size_bytes'));
        self::assertSame(33554432, $this->readProtectedInt($consumer, 'max_queue_size_bytes'));

        self::assertTrue($consumer->track([
            'userId' => 'user-1',
            'event' => 'Small Event',
            'properties' => ['payload' => str_repeat('a', 100)],
        ]));

        [$accepted, $log] = $this->captureErrorLog(
            static fn (): bool => $consumer->track([
                'userId' => 'user-1',
                'event' => 'Oversized Default Event',
                'properties' => ['payload' => str_repeat('a', 32000)],
            ])
        );

        self::assertFalse($accepted);
        self::assertStringContainsString('Item size is larger than 32000 bytes', $log);
    }

    public function testCustomMaxItemSizeBytesAllowsItemsLargerThanDefault(): void
    {
        $consumer = $this->createConsumer([
            'flush_at' => 1,
            'max_item_size_bytes' => 64000,
        ]);

        $accepted = $consumer->track([
            'userId' => 'user-1',
            'event' => 'Large Allowed Event',
            'properties' => ['payload' => str_repeat('a', 40000)],
        ]);

        self::assertTrue($accepted);
        self::assertCount(1, $consumer->flushedBatches);
        self::assertCount(1, $consumer->flushedBatches[0]);
    }

    public function testCustomMaxItemSizeBytesRejectsItemsAtOrAboveLimit(): void
    {
        $consumer = $this->createConsumer([
            'flush_at' => 10,
            'max_item_size_bytes' => 200,
        ]);

        $smallItem = [
            'userId' => 'user-1',
            'event' => 'Small',
        ];
        $largeItem = [
            'userId' => 'user-1',
            'event' => 'Too Large',
            'properties' => ['payload' => str_repeat('x', 200)],
        ];

        self::assertTrue($consumer->track($smallItem));

        [$accepted, $log] = $this->captureErrorLog(
            static fn (): bool => $consumer->track($largeItem)
        );

        self::assertFalse($accepted);
        self::assertStringContainsString('Item size is larger than 200 bytes', $log);

        self::assertTrue($consumer->flush());
        self::assertCount(1, $consumer->flushedBatches);
        self::assertSame([$smallItem], $consumer->flushedBatches[0]);
    }

    public function testCustomMaxQueueSizeBytesEnforcesQueueCap(): void
    {
        $item = [
            'userId' => 'user-1',
            'event' => 'Queued Event',
            'properties' => ['payload' => str_repeat('q', 150)],
        ];
        $singleItemQueueSize = mb_strlen(serialize([$item]), '8bit');

        $consumer = $this->createConsumer([
            'flush_at' => 10,
            'max_queue_size_bytes' => $singleItemQueueSize,
        ]);

        self::assertTrue($consumer->track($item));

        [$accepted, $log] = $this->captureErrorLog(
            static fn (): bool => $consumer->track($item)
        );

        self::assertFalse($accepted);
        self::assertStringContainsString(
            'Queue size is larger than ' . $singleItemQueueSize . ' bytes',
            $log
        );

        self::assertTrue($consumer->flush());
        self::assertCount(1, $consumer->flushedBatches);
        self::assertCount(1, $consumer->flushedBatches[0]);

        self::assertTrue($consumer->track($item));
    }

    public function testHigherMaxQueueSizeBytesPermitsAdditionalItems(): void
    {
        $item = [
            'userId' => 'user-1',
            'event' => 'Queued Event',
            'properties' => ['payload' => str_repeat('q', 150)],
        ];
        $singleItemQueueSize = mb_strlen(serialize([$item]), '8bit');

        $consumer = $this->createConsumer([
            'flush_at' => 10,
            'max_queue_size_bytes' => $singleItemQueueSize * 3,
        ]);

        self::assertTrue($consumer->track($item));
        self::assertTrue($consumer->track($item));
        self::assertTrue($consumer->flush());
        self::assertCount(1, $consumer->flushedBatches);
        self::assertCount(2, $consumer->flushedBatches[0]);
    }

    public function testNumericStringSizeOptionsAreParsed(): void
    {
        $consumer = $this->createConsumer([
            'max_item_size_bytes' => '64000',
            'max_queue_size_bytes' => '1048576',
        ]);

        self::assertSame(64000, $this->readProtectedInt($consumer, 'max_item_size_bytes'));
        self::assertSame(1048576, $this->readProtectedInt($consumer, 'max_queue_size_bytes'));
    }

    /**
     * @dataProvider invalidSizeOptionProvider
     * @param mixed $invalidValue
     */
    public function testInvalidSizeOptionsFallBackToDefaults($invalidValue): void
    {
        [$consumer, $log] = $this->captureErrorLog(
            fn (): QueueConsumer => $this->createConsumer([
                'max_item_size_bytes' => $invalidValue,
                'max_queue_size_bytes' => $invalidValue,
            ])
        );

        self::assertSame(32000, $this->readProtectedInt($consumer, 'max_item_size_bytes'));
        self::assertSame(33554432, $this->readProtectedInt($consumer, 'max_queue_size_bytes'));
        self::assertStringContainsString('Max queue size in bytes must be a positive integer', $log);
        self::assertStringContainsString('Max item size in bytes must be a positive integer', $log);
    }

    /**
     * @return array<string,array{0:mixed}>
     */
    public static function invalidSizeOptionProvider(): array
    {
        return [
            'zero' => [0],
            'negative int' => [-10],
            'zero string' => ['0'],
            'negative string' => ['-5'],
            'non-numeric string' => ['invalid'],
            'float' => [12.5],
            'true bool' => [true],
            'false bool' => [false],
            'array' => [[]],
        ];
    }

    public function testClientForwardsSizeOptionsToConsumer(): void
    {
        $client = new Client('secret', [
            'max_item_size_bytes' => 64000,
            'max_queue_size_bytes' => 1048576,
        ]);

        $consumer = $client->getConsumer();
        self::assertInstanceOf(QueueConsumer::class, $consumer);
        self::assertSame(64000, $this->readProtectedInt($consumer, 'max_item_size_bytes'));
        self::assertSame(1048576, $this->readProtectedInt($consumer, 'max_queue_size_bytes'));
    }

    /**
     * @param array<string,mixed> $options
     */
    private function createConsumer(array $options = []): QueueConsumer
    {
        return new class ('secret', $options) extends QueueConsumer {
            protected string $type = 'TestQueue';

            /**
             * @var array<int,array<int,mixed>>
             */
            public array $flushedBatches = [];

            /**
             * @param array<int,mixed> $batch
             */
            public function flushBatch(array $batch): bool
            {
                $this->flushedBatches[] = $batch;

                return true;
            }
        };
    }

    private function readProtectedInt(QueueConsumer $consumer, string $property): int
    {
        $reflection = new ReflectionProperty(QueueConsumer::class, $property);
        if (PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }

        return (int)$reflection->getValue($consumer);
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return array{0:T,1:string}
     */
    private function captureErrorLog(callable $callback): array
    {
        $logFile = tempnam(sys_get_temp_dir(), 'rudder-php-sdk-log-');
        self::assertNotFalse($logFile);

        $previousLog = ini_set('error_log', $logFile);
        try {
            $result = $callback();
            $logContents = file_get_contents($logFile);
            self::assertNotFalse($logContents);

            return [$result, $logContents];
        } finally {
            if ($previousLog !== false) {
                ini_set('error_log', $previousLog);
            }
            @unlink($logFile);
        }
    }
}
