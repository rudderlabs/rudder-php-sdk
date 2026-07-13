<?php

declare(strict_types=1);

namespace Rudder\Consumer;

class Socket extends QueueConsumer
{
    protected string $type = 'Socket';
    private bool $socket_failed = false;

    /**
     * Creates a new socket consumer for dispatching async requests immediately.
     *
     * @param string $secret
     * @param array $options
     *     number "timeout" - the timeout for connecting
     *     function "error_handler" - function called back on errors.
     *     bool "debug" - whether to use debug output, wait for response.
     */
    public function __construct(string $secret, array $options = [])
    {
        if (!isset($options['timeout'])) {
            $options['timeout'] = 5;
        }

        if (!isset($options['tls'])) {
            $options['tls'] = '';
        }

        parent::__construct($secret, $options);
    }

    public function flushBatch($batch): bool
    {
        $socket = $this->createSocket();

        if (!$socket) {
            return false;
        }

        $payload = $this->payload($batch);
        $payload = json_encode($payload);

        $body = $this->createBody($this->host, $payload);
        if ($body === false) {
            return false;
        }

        return $this->makeRequest($socket, $body);
    }

    /**
     * Open a connection to the target host.
     *
     * @return false|resource
     */
    protected function createSocket()
    {
        if ($this->socket_failed) {
            return false;
        }

        $protocol = $this->options['tls'] ? 'tls' : 'ssl';
        $host = $this->host;
        $port = 443;
        $timeout = $this->options['timeout'];

        // Open our socket to the API Server.
        $socket = @pfsockopen(
            $protocol . '://' . $host,
            $port,
            $errno,
            $errstr,
            $timeout
        );

        // If we couldn't open the socket, handle the error.
        if ($socket === false) {
            $this->handleError($errno, $errstr);
            $this->socket_failed = true;
        }

        return $socket;
    }

    /**
     * Create the request body.
     *
     * @param string $host
     * @param string $content
     * @return string body
     */
    private function createBody(string $host, string $content)
    {
        $req = "POST /v1/batch HTTP/1.1\r\n";
        $req .= 'Host: ' . $host . "\r\n";
        $req .= "Content-Type: application/json\r\n";
        $req .= 'Authorization: Basic ' . base64_encode($this->secret . ':') . "\r\n";
        $req .= "Accept: application/json\r\n";

        // Send user agent in the form of {library_name}/{library_version} as per RFC 7231.
        $content_json = json_decode($content, true);
        $isSingleEventBatch = count($content_json['batch']) === 1;
        $library = $content_json['batch'][0]['context']['library'];
        $libName = $library['name'];
        $libVersion = $library['version'];
        $req .= "User-Agent: $libName/$libVersion\r\n";

        // Add AnonymousId request header as required from server in order to retain event ordering
        if ($isSingleEventBatch) {
            $anonymousIdRequestHeader = '';
            if (!empty($content_json['batch'][0]['userId'])) {
                $anonymousIdRequestHeader = $content_json['batch'][0]['userId'];
            } elseif (!empty($content_json['batch'][0]['anonymousId'])) {
                $anonymousIdRequestHeader = $content_json['batch'][0]['anonymousId'];
            }

            if (!empty($anonymousIdRequestHeader)) {
                $encodedAnonymousId = base64_encode($anonymousIdRequestHeader);
                $req .= "AnonymousId: $encodedAnonymousId\r\n";
            }
        }

        // Compress content if compress_request is true
        if ($this->compress_request) {
            $content = gzencode($content);

            $req .= "Content-Encoding: gzip\r\n";
        }

        $req .= 'Content-length: ' . strlen($content) . "\r\n";
        $req .= "\r\n";
        $req .= $content;

        // Verify payload size is below 512KB
        if (strlen($req) >= 500 * 1024) {
            $msg = 'Payload size is larger than 512KB';
            /** @noinspection ForgottenDebugOutputInspection */
            error_log('[Analytics][' . $this->type . '] ' . $msg);

            return false;
        }

//      Uncomment for local debugging
//        var_dump('===============================================');
//        var_dump($req);
//        var_dump('===============================================');

        return $req;
    }

    /**
     * Attempt to write the request to the socket, wait for response if debug
     * mode is enabled.
     *
     * @param resource|false $socket the handle for the socket
     * @param string $req request body
     * @return bool
     */
    private function makeRequest($socket, string $req): bool
    {
        $bytes_total = strlen($req);
        $retries = 0;

        while (true) {
            $bytes_written = 0;
            $closed = false;
            $res = [
                'status'  => 0,
                'message' => '',
                'headers' => [],
            ];

            // Send request to server
            while (!$closed && $bytes_written < $bytes_total) {
                $written = @fwrite($socket, substr($req, $bytes_written));
                if ($written === false) {
                    $this->handleError(13, 'Failed to write to socket.');
                    $closed = true;
                } else {
                    $bytes_written += $written;
                }
            }

            // Get response for request
            $statusCode = 0;

            if (!$closed) {
                $response = fread($socket, 2048);
                $res = self::parseResponse(is_string($response) ? $response : '');
                $statusCode = (int)$res['status'];
            }
            fclose($socket);

            // If status code is successful, return true
            if ($this->isSuccessStatusCode($statusCode)) {
                return true;
            }

            if ($this->canRetryStatusCode($statusCode, $retries)) {
                $retries++;
                usleep($this->retryDelayInMicroseconds($retries, $res['headers']));
                $socket = $this->createSocket();
                if (!$socket) {
                    return false;
                }

                continue;
            }

            $this->handleError((int)$res['status'], $res['message']);

            return false;
        }
    }

    /**
     * Parse our response from the server, check header and body.
     * @param string $res
     * @return array
     *     string $status HTTP code, e.g. "200"
     *     string $message JSON response from the api
     */
    private static function parseResponse(string $res): array
    {
        $responseParts = explode("\r\n\r\n", $res, 2);
        $headerBlock = $responseParts[0] ?? '';
        $headerLines = preg_split('/\r\n|\n|\r/', $headerBlock) ?: [];
        $first = array_shift($headerLines) ?? '';

        // Response comes back as HTTP/1.1 200 OK
        // Final line contains HTTP response.
        $statusParts = explode(' ', $first, 3);
        $status = $statusParts[1] ?? 0;
        $message = $statusParts[2] ?? '';
        $headers = [];
        foreach ($headerLines as $headerLine) {
            $headerParts = explode(':', $headerLine, 2);
            if (count($headerParts) === 2) {
                $headers[strtolower(trim($headerParts[0]))] = trim($headerParts[1]);
            }
        }

        return [
            'status'  => $status ?? null,
            'message' => $message,
            'headers' => $headers,
        ];
    }
}
