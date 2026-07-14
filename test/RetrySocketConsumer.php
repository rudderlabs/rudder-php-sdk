<?php

declare(strict_types=1);

namespace Rudder\Test;

use Rudder\Consumer\Socket;
use RuntimeException;

class RetrySocketConsumer extends Socket
{
    /**
     * @var array<int,resource>
     */
    private array $clientSockets = [];

    /**
     * @var array<int,resource>
     */
    private array $serverSockets = [];

    private int $createdSockets = 0;

    /**
     * @param array<string,mixed> $options
     */
    public function __construct(string $secret, array $options = [])
    {
        parent::__construct(
            $secret,
            array_merge(
                [
                    'compress_request' => false,
                    'max_retries' => 1,
                    'retry_base_delay' => 0,
                    'max_retry_delay' => 0,
                    'retry_jitter_ratio' => 0,
                ],
                $options
            )
        );
    }

    /**
     * @param array<string,string> $headers
     */
    public function queueResponse(int $statusCode, array $headers = [], string $body = ''): void
    {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($sockets === false) {
            throw new RuntimeException('Unable to create socket pair for retry test.');
        }

        fwrite($sockets[1], self::response($statusCode, $headers, $body));

        $this->clientSockets[] = $sockets[0];
        $this->serverSockets[] = $sockets[1];
    }

    /**
     * @return false|resource
     */
    protected function createSocket()
    {
        $this->createdSockets++;

        return array_shift($this->clientSockets) ?: false;
    }

    public function createdSockets(): int
    {
        return $this->createdSockets;
    }

    public function requestForSocket(int $offset): string
    {
        $socket = $this->serverSockets[$offset] ?? null;
        if (!is_resource($socket)) {
            throw new RuntimeException("No server socket exists at offset $offset.");
        }

        $request = stream_get_contents($socket);
        if ($request === false) {
            throw new RuntimeException("Unable to read request from socket at offset $offset.");
        }

        return $request;
    }

    public function closeQueuedSockets(): void
    {
        foreach (array_merge($this->clientSockets, $this->serverSockets) as $socket) {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }

        $this->clientSockets = [];
        $this->serverSockets = [];
    }

    public function __destruct()
    {
        parent::__destruct();
        $this->closeQueuedSockets();
    }

    /**
     * @param array<string,string> $headers
     */
    private static function response(int $statusCode, array $headers, string $body): string
    {
        $reason = [
            200 => 'OK',
            400 => 'Bad Request',
            429 => 'Too Many Requests',
            503 => 'Service Unavailable',
        ][$statusCode] ?? 'Status';

        $response = "HTTP/1.1 $statusCode $reason\r\n";
        foreach ($headers as $name => $value) {
            $response .= "$name: $value\r\n";
        }

        $response .= 'Content-Length: ' . strlen($body) . "\r\n";
        $response .= "\r\n";
        $response .= $body;

        return $response;
    }
}
