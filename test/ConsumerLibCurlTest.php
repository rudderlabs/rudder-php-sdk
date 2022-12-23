<?php

declare(strict_types=1);

namespace Rudder\Test;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
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

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }
}
