<?php

declare(strict_types=1);

namespace Rudder\Stub;

class SocketServerStub
{
    protected $sock;
    protected $client;
    protected $response;

    public function __construct()
    {
        $this->sock = socket_create(AF_INET, SOCK_STREAM, 0);

        // Bind the socket to an address/port
        socket_bind($this->sock, 'localhost', 0);
    }

    public function listen()
    {
        // Start listening for connections
        socket_listen($this->sock);

        // Accept incoming requests and handle them as child processes.
        $this->client = socket_accept($this->sock);
    }

    public function getServerRoot()
    {
        return 'localhost:0';
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function read()
    {
        // Read the input from the client &#8211; 1024 bytes
        $input = socket_read($this->client, 1024);
        return $input;
    }

    public function __destruct()
    {
        socket_close($this->sock);
    }
}
