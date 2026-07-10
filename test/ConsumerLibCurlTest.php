<?php

declare(strict_types=1);

namespace Rudder\Test;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Rudder\Client;
use RuntimeException;

class ConsumerLibCurlTest extends TestCase
{
    protected static MockWebServer $server;

    public static function setUpBeforeClass(): void
    {
        // Looking for .env at the root directory
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
        date_default_timezone_set('UTC');

        self::$server = new MockWebServer();
        self::$server->start();
        self::$server->setResponseOfPath('/v1/batch', new Response(
            'OK',
            [ 'Cache-Control' => 'no-cache' ],
            200
        ));
    }

    public function setUp(): void
    {
    }

    public function testTrack(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'debug' => true,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'lib_curl',
            ]
        );

        # Should error out with debug on.
        self::assertTrue($client->track(['user_id' => 'some-user2', 'event' => 'Socket PHP Event']));
        $client->__destruct();
    }

    public function testIdentify(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'debug' => true,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'lib_curl',
            ]
        );

        self::assertTrue($client->identify([
            'userId' => 'lib-curl-identify',
            'traits' => [
                'loves_php' => false,
                'type'      => 'consumer lib-curl test',
                'birthday'  => time(),
            ],
        ]));

        $client->__destruct();
    }

    public function testGroup(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'debug' => true,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'lib_curl',
            ]
        );

        self::assertTrue($client->group([
            'userId'  => 'lib-curl-group',
            'groupId' => 'group-id',
            'traits'  => [
                'type' => 'consumer lib-curl test',
            ],
        ]));

        $client->__destruct();
    }

    public function testPage(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'debug' => true,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'lib_curl',
            ]
        );

        self::assertTrue($client->page([
            'userId'     => 'lib-curl-page',
            'name'       => 'analytics-php',
            'category'   => 'fork-curl',
            'properties' => ['url' => 'https://a.url/'],
        ]));

        $client->__destruct();
    }

    public function testScreen(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'debug' => true,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'lib_curl',
            ]
        );

        self::assertTrue($client->page([
            'anonymousId' => 'lib-curl-screen',
            'name'        => 'grand theft auto',
            'category'    => 'fork-curl',
            'properties'  => [],
        ]));

        $client->__destruct();
    }

    public function testAlias(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'debug' => true,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'lib_curl',
            ]
        );

        self::assertTrue($client->alias([
            'previousId' => 'lib-curl-alias',
            'userId'     => 'user-id',
        ]));

        $client->__destruct();
    }

    public function testRequestCompression(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => true,
                'ssl' => false,
                'debug' => true,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'lib_curl',
                'error_handler' => function ($errno, $errmsg) {
                    throw new RuntimeException($errmsg, $errno);
                },
            ]
        );

        # Should error out with debug on.
        self::assertTrue($client->track(['user_id' => 'some-user', 'event' => 'Socket PHP Event']));

        $client->__destruct();
    }

    public function testLargeMessageSizeError(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'debug' => true,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'lib_curl',
            ]
        );

        $big_property = str_repeat('a', 32 * 1024);

        self::assertFalse(
            $client->track(
                [
                    'userId'     => 'some-user',
                    'event'      => 'Super Large PHP Event',
                    'properties' => ['big_property' => $big_property],
                ]
            ) && $client->flush()
        );

        $client->__destruct();
    }

    public function testRateLimitFailureAfterRetryBudgetIsExhausted(): void
    {
        self::$server->setResponseOfPath('/v1/batch', new Response(
            'Rate limited',
            [ 'Retry-After' => '1' ],
            429
        ));

        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'debug' => true,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'lib_curl',
                'flush_at' => 1,
                'max_retries' => 0,
                'retry_jitter_ratio' => 0,
            ]
        );

        try {
            self::assertFalse($client->track(['user_id' => 'some-user', 'event' => 'Rate Limited PHP Event']));
            $client->__destruct();
        } finally {
            self::$server->setResponseOfPath('/v1/batch', new Response(
                'OK',
                [ 'Cache-Control' => 'no-cache' ],
                200
            ));
        }
    }

    public function testRetriesRateLimitUntilSuccess(): void
    {
        $requestsBefore = $this->requestCount();
        self::$server->setResponseOfPath('/v1/batch', new ResponseStack(
            new Response('Rate limited', [], 429),
            new Response('OK', [ 'Cache-Control' => 'no-cache' ], 200)
        ));

        $client = $this->createRetryClient([
            'max_retries' => 3,
            'retry_base_delay' => 0,
            'max_retry_delay' => 0,
        ]);

        try {
            self::assertTrue($client->track(['user_id' => 'some-user', 'event' => 'Retried PHP Event']));
            self::assertSame(2, $this->requestCount() - $requestsBefore);

            $firstRequest = self::$server->getRequestByOffset($requestsBefore);
            $retryRequest = self::$server->getRequestByOffset($requestsBefore + 1);
            self::assertNotNull($firstRequest);
            self::assertNotNull($retryRequest);
            self::assertSame('POST', $firstRequest->getRequestMethod());
            self::assertSame('/v1/batch', $firstRequest->getRequestUri());
            self::assertSame($firstRequest->getRequestMethod(), $retryRequest->getRequestMethod());
            self::assertSame($firstRequest->getRequestUri(), $retryRequest->getRequestUri());
            self::assertSame($firstRequest->getInput(), $retryRequest->getInput());
            self::assertArrayHasKey('Authorization', $firstRequest->getHeaders());
            self::assertArrayHasKey('Authorization', $retryRequest->getHeaders());
            self::assertSame(
                $firstRequest->getHeaders()['Authorization'],
                $retryRequest->getHeaders()['Authorization']
            );
        } finally {
            $client->__destruct();
            $this->resetBatchResponse();
        }
    }

    /**
     * @dataProvider commonServerErrorProvider
     */
    public function testRetriesCommonServerErrorsUntilSuccess(int $statusCode): void
    {
        $requestsBefore = $this->requestCount();
        self::$server->setResponseOfPath('/v1/batch', new ResponseStack(
            new Response('Server error', [], $statusCode),
            new Response('OK', [ 'Cache-Control' => 'no-cache' ], 200)
        ));

        $client = $this->createRetryClient([
            'max_retries' => 3,
            'retry_base_delay' => 0,
            'max_retry_delay' => 0,
        ]);

        try {
            self::assertTrue($client->track([
                'user_id' => 'some-user',
                'event' => "Retried $statusCode PHP Event",
            ]));
            self::assertSame(2, $this->requestCount() - $requestsBefore);
        } finally {
            $client->__destruct();
            $this->resetBatchResponse();
        }
    }

    /**
     * @return array<string,array{int}>
     */
    public static function commonServerErrorProvider(): array
    {
        return [
            '500 Internal Server Error' => [500],
            '502 Bad Gateway' => [502],
            '503 Service Unavailable' => [503],
            '504 Gateway Timeout' => [504],
        ];
    }

    public function testDoesNotRetryTerminalClientErrors(): void
    {
        $requestsBefore = $this->requestCount();
        self::$server->setResponseOfPath('/v1/batch', new Response('Bad request', [], 400));

        $client = $this->createRetryClient([
            'max_retries' => 3,
            'retry_base_delay' => 0,
            'max_retry_delay' => 0,
        ]);

        try {
            self::assertFalse($client->track(['user_id' => 'some-user', 'event' => 'Bad Request PHP Event']));
            self::assertSame(1, $this->requestCount() - $requestsBefore);
        } finally {
            $client->__destruct();
            $this->resetBatchResponse();
        }
    }

    public function testRetriesOnlyWithinRetryBudget(): void
    {
        $requestsBefore = $this->requestCount();
        self::$server->setResponseOfPath('/v1/batch', new ResponseStack(
            new Response('Rate limited', [], 429),
            new Response('Rate limited', [], 429),
            new Response('OK', [ 'Cache-Control' => 'no-cache' ], 200)
        ));

        $client = $this->createRetryClient([
            'max_retries' => 1,
            'retry_base_delay' => 0,
            'max_retry_delay' => 0,
        ]);

        try {
            self::assertFalse($client->track(['user_id' => 'some-user', 'event' => 'Retry Budget PHP Event']));
            self::assertSame(2, $this->requestCount() - $requestsBefore);
        } finally {
            $client->__destruct();
            $this->resetBatchResponse();
        }
    }

    public function testHonorsRetryAfterOnRateLimit(): void
    {
        $requestsBefore = $this->requestCount();
        self::$server->setResponseOfPath('/v1/batch', new ResponseStack(
            new Response('Rate limited', [ 'Retry-After' => '1' ], 429),
            new Response('OK', [ 'Cache-Control' => 'no-cache' ], 200)
        ));

        $client = $this->createRetryClient([
            'max_retries' => 3,
            'retry_base_delay' => 0,
            'max_retry_delay' => 0,
        ]);

        $start = microtime(true);
        try {
            self::assertTrue($client->track(['user_id' => 'some-user', 'event' => 'Retry After PHP Event']));
            self::assertGreaterThanOrEqual(1.0, microtime(true) - $start);
            self::assertSame(2, $this->requestCount() - $requestsBefore);
        } finally {
            $client->__destruct();
            $this->resetBatchResponse();
        }
    }

    /**
     * @param array<string,mixed> $overrides
     */
    private function createRetryClient(array $overrides = []): Client
    {
        return new Client(
            $_ENV['WRITE_KEY'],
            array_merge(
                [
                    'compress_request' => false,
                    'ssl' => false,
                    'debug' => true,
                    'data_plane_url' => self::$server->getServerRoot(),
                    'consumer' => 'lib_curl',
                    'flush_at' => 1,
                    'retry_jitter_ratio' => 0,
                ],
                $overrides
            )
        );
    }

    private function requestCount(): int
    {
        $count = 0;
        while (self::$server->getRequestByOffset($count) !== null) {
            $count++;
        }

        return $count;
    }

    private function resetBatchResponse(): void
    {
        self::$server->setResponseOfPath('/v1/batch', new Response(
            'OK',
            [ 'Cache-Control' => 'no-cache' ],
            200
        ));
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }
}
