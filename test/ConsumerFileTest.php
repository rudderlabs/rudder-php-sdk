<?php

declare(strict_types=1);

namespace Rudder\Test;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Rudder\Client;

class ConsumerFileTest extends TestCase
{
    protected static MockWebServer $server;
    private string $filename = '/tmp/analytics.log';

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
        $this->clearLog();
    }

    private function clearLog(): void
    {
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function tearDown(): void
    {
        $this->clearLog();
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
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'file',
                'filename' => $this->filename,
            ]
        );

        self::assertTrue($client->track([
            'userId'    => 'some-user',
            'event'     => 'File PHP Event - Microtime',
            'timestamp' => microtime(true),
        ]));
        $this->checkWritten('track');
        $client->__destruct();
    }

    public function checkWritten($type): void
    {
        exec('wc -l ' . $this->filename, $output);
        $out = trim($output[0]);
        self::assertSame($out, '1 ' . $this->filename);
        $str = file_get_contents($this->filename);
        $json = json_decode(trim($str), false);
        self::assertSame($type, $json->type);
        unlink($this->filename);
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
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'file',
                'filename' => $this->filename,
            ]
        );

        self::assertTrue($client->identify([
            'userId' => 'Calvin',
            'traits' => [
                'loves_php' => false,
                'type'      => 'analytics.log',
                'birthday'  => time(),
            ],
        ]));
        $this->checkWritten('identify');
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
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'file',
                'filename' => $this->filename,
            ]
        );

        self::assertTrue($client->group([
            'userId'  => 'user-id',
            'groupId' => 'group-id',
            'traits'  => [
                'type' => 'consumer analytics.log test',
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
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'file',
                'filename' => $this->filename,
            ]
        );

        self::assertTrue($client->page([
            'userId'     => 'user-id',
            'name'       => 'analytics-php',
            'category'   => 'analytics.log',
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
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'file',
                'filename' => $this->filename,
            ]
        );

        self::assertTrue($client->screen([
            'userId'     => 'userId',
            'name'       => 'grand theft auto',
            'category'   => 'analytics.log',
            'properties' => [],
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
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'file',
                'filename' => $this->filename,
            ]
        );

        self::assertTrue($client->alias([
            'previousId' => 'previous-id',
            'userId'     => 'user-id',
        ]));
        $this->checkWritten('alias');
        $client->__destruct();
    }

    public function testSend(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'file',
                'filename' => $this->filename,
            ]
        );

        for ($i = 0; $i < 200; ++$i) {
            $client->track([
                'userId' => 'userId',
                'event'  => 'event',
            ]);
        }

        exec("php --define date.timezone=UTC examples/SendBatchFromFile.php --secret $__WRITE_KEY__ --compress_request false --ssl false --data_plane_url $__DATAPLANE_URL__ --file $this->filename", $output);
        self::assertSame('sent 200 from 200 requests successfully', trim($output[0]));
        self::assertFileDoesNotExist($this->filename);
        $client->__destruct();
    }

    public function testProductionProblems(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        // Open to a place where we should not have write access.
        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'file',
                'filename' => '/dev/xxxxxxx',
            ]
        );

        $tracked = $client->track(['userId' => 'some-user', 'event' => 'my event']);
        self::assertFalse($tracked);
        $client->__destruct();
    }

    public function testFileSecurityCustom(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        // Open to a place where we should not have write access.
        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer'        => 'file',
                'filename'        => $this->filename,
                'filepermissions' => 0600,
            ]
        );

        $client->track(['userId' => 'some_user', 'event' => 'File PHP Event']);
        self::assertEquals(0600, (fileperms($this->filename) & 0777));
        $client->__destruct();
    }

    public function testFileSecurityDefaults(): void
    {
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = self::$server->getServerRoot();

        $client = new Client(
            $__WRITE_KEY__,
            [
                'compress_request' => false,
                'ssl' => false,
                'data_plane_url' => $__DATAPLANE_URL__,
                'consumer' => 'file',
                'filename' => $this->filename,
            ]
        );

        $client->track(['userId' => 'some_user', 'event' => 'File PHP Event']);
        self::assertEquals(0644, (fileperms($this->filename) & 0777));
        $client->__destruct();
    }
}
