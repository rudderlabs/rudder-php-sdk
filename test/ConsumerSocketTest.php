<?php

declare(strict_types=1);

namespace Rudder\Test;

use Dotenv\Dotenv;
use Exception;
use PHPUnit\Framework\TestCase;
use Rudder\Client;
use RuntimeException;

class ConsumerSocketTest extends TestCase
{
    private Client $client;

    public function setUp(): void
    {
        // Looking for .env at the root directory
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Retrieve env variables
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];

        date_default_timezone_set('UTC');
        $this->client = new Client(
            $__WRITE_KEY__,
            ['consumer' => 'socket']
        );
    }

    public function testTrack(): void
    {
        self::assertTrue(
            $this->client->track(
                [
                    'userId' => 'some-user',
                    'event'  => 'Socket PHP Event',
                ]
            )
        );
    }

    public function testIdentify(): void
    {
        self::assertTrue(
            $this->client->identify(
                [
                    'userId' => 'Calvin',
                    'traits' => [
                        'loves_php' => false,
                        'birthday'  => time(),
                    ],
                ]
            )
        );
    }

    public function testGroup(): void
    {
        self::assertTrue(
            $this->client->group(
                [
                    'userId'  => 'user-id',
                    'groupId' => 'group-id',
                    'traits'  => [
                        'type' => 'consumer socket test',
                    ],
                ]
            )
        );
    }

    public function testPage(): void
    {
        self::assertTrue(
            $this->client->page(
                [
                    'userId'     => 'user-id',
                    'name'       => 'analytics-php',
                    'category'   => 'socket',
                    'properties' => ['url' => 'https://a.url/'],
                ]
            )
        );
    }

    public function testScreen(): void
    {
        self::assertTrue(
            $this->client->screen(
                [
                    'anonymousId' => 'anonymousId',
                    'name'        => 'grand theft auto',
                    'category'    => 'socket',
                    'properties'  => [],
                ]
            )
        );
    }

    public function testAlias(): void
    {
        self::assertTrue(
            $this->client->alias(
                [
                    'previousId' => 'some-socket',
                    'userId'     => 'new-socket',
                ]
            )
        );
    }

    public function testShortTimeout(): void
    {
        // Looking for .env at the root directory
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Retrieve env variables
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];

        $client = new Client(
            $__WRITE_KEY__,
            [
                'timeout'  => 0.01,
                'consumer' => 'socket',
            ]
        );

        self::assertTrue(
            $client->track(
                [
                    'userId' => 'some-user',
                    'event'  => 'Socket PHP Event',
                ]
            )
        );

        self::assertTrue(
            $client->identify(
                [
                    'userId' => 'some-user',
                    'traits' => [],
                ]
            )
        );

        $client->__destruct();
    }

    public function testProductionProblems(): void
    {
        // Looking for .env at the root directory
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Retrieve env variables
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];

        $client = new Client(
            $__WRITE_KEY__,
            [
                'consumer'      => 'socket',
                'error_handler' => function () {
                    throw new Exception('Was called');
                },
            ]
        );

        // Shouldn't error out without debug on.
        self::assertTrue($client->track(['user_id' => 'some-user', 'event' => 'Production Problems']));
        $client->__destruct();
    }

    public function testDebugProblems(): void
    {
        // Looking for .env at the root directory
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Retrieve env variables
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];

        $options = [
            'debug'         => true,
            'consumer'      => 'socket',
            'error_handler' => function ($errno, $errmsg) {
                if ($errno !== 400) {
                    throw new Exception('Response is not 400');
                }
            },
        ];

        $client = new Client($__WRITE_KEY__, $options);

        // Should error out with debug on.
        self::assertTrue($client->track(['user_id' => 'some-user', 'event' => 'Socket PHP Event']));
        $client->__destruct();
    }

    public function testLargeMessage(): void
    {
        // Looking for .env at the root directory
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Retrieve env variables
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];

        $options = [
            'debug'    => true,
            'consumer' => 'socket',
        ];

        $client = new Client($__WRITE_KEY__, $options);

        $big_property = str_repeat('a', 10000);

        self::assertTrue(
            $client->track(
                [
                    'userId'     => 'some-user',
                    'event'      => 'Super Large PHP Event',
                    'properties' => ['big_property' => $big_property],
                ]
            )
        );

        $client->__destruct();
    }

    public function testLargeMessageSizeError(): void
    {
        // Looking for .env at the root directory
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Retrieve env variables
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];

        $options = [
            'debug'    => true,
            'consumer' => 'socket',
        ];

        $client = new Client($__WRITE_KEY__, $options);

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

    public function testConnectionError(): void
    {
        // Looking for .env at the root directory
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Retrieve env variables
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];

        $this->expectException(RuntimeException::class);
        $client = new Client(
            $__WRITE_KEY__,
            [
                'consumer'          => 'socket',
                'data_plane_url'    => 'hosted.rudderlabs.com.dummy',
                'error_handler'     => function ($errno, $errmsg) {
                    throw new RuntimeException($errmsg, $errno);
                },
            ]
        );

        $client->track(['user_id' => 'some-user', 'event' => 'Event']);
        $client->__destruct();
    }

    public function testRequestCompression(): void
    {
        // Looking for .env at the root directory
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Retrieve env variables
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];

        $options = [
            'compress_request' => true,
            'consumer'         => 'socket',
            'error_handler'    => function ($errno, $errmsg) {
                throw new RuntimeException($errmsg, $errno);
            },
        ];

        $client = new Client($__WRITE_KEY__, $options);

        # Should error out with debug on.
        self::assertTrue($client->track(['user_id' => 'some-user', 'event' => 'Socket PHP Event']));
        $client->__destruct();
    }
}
