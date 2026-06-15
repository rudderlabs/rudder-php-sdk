<?php

declare(strict_types=1);

namespace Rudder\Consumer;

class LibCurl extends QueueConsumer
{
    protected string $type = 'LibCurl';

    /**
     * Make a sync request to our API. If debug is
     * enabled, we wait for the response
     * and retry transient responses with exponential backoff.
     * @param array $messages array of all the messages to send
     * @return bool whether the request succeeded
     */
    public function flushBatch(array $messages): bool
    {
        $body = $this->payload($messages);
        $payload = json_encode($body);
        $isSingleEventBatch = count($messages) === 1;
        $secret = $this->secret;

        if ($this->compress_request) {
            $payload = gzencode($payload);
        }

        $path = '/v1/batch';
        $url = $this->protocol . $this->host . $path;

        $retries = 0;

        while (true) {
            // open connection
            $ch = curl_init();
            $responseHeaders = [];

            // set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_USERPWD, $secret . ':');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->curl_connecttimeout);

            // set variables for headers
            $header = [];
            $header[] = 'Content-Type: application/json';

            if ($this->compress_request) {
                $header[] = 'Content-Encoding: gzip';
            }

            // Send user agent in the form of {library_name}/{library_version} as per RFC 7231.
            $library = $messages[0]['context']['library'];
            $libName = $library['name'];
            $libVersion = $library['version'];
            $header[] = "User-Agent: $libName/$libVersion";

            // Add AnonymousId request header as required from server in order to retain event ordering
            if ($isSingleEventBatch) {
                $anonymousIdRequestHeader = '';
                if (!empty($messages[0]['userId'])) {
                    $anonymousIdRequestHeader = $messages[0]['userId'];
                } elseif (!empty($messages[0]['anonymousId'])) {
                    $anonymousIdRequestHeader = $messages[0]['anonymousId'];
                }

                if (!empty($anonymousIdRequestHeader)) {
                    $encodedAnonymousId = base64_encode($anonymousIdRequestHeader);
                    $header[] = "AnonymousId: $encodedAnonymousId";
                }
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $ch,
                CURLOPT_HEADERFUNCTION,
                static function ($_curl, string $headerLine) use (&$responseHeaders): int {
                    $headerLength = strlen($headerLine);
                    $headerParts = explode(':', $headerLine, 2);
                    if (count($headerParts) === 2) {
                        $responseHeaders[strtolower(trim($headerParts[0]))] = trim($headerParts[1]);
                    }

                    return $headerLength;
                }
            );

            // retry failed requests just once to diminish impact on performance
            $responseContent = curl_exec($ch);

            $err = curl_error($ch);
            if ($err) {
                $errorCode = curl_errno($ch);
                if (PHP_VERSION_ID < 80000) {
                    curl_close($ch);
                }

                if ($retries < $this->max_retries) {
                    $retries++;
                    usleep($this->retryDelayInMicroseconds($retries));
                    continue;
                }

                $this->handleError($errorCode, $err);
                return false;
            }

            $responseCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

//            Uncomment for local debugging
//            var_dump('===============================================');
//            var_dump("Request body:\n");
//            var_dump(json_encode($body));
//            var_dump("\nRequest headers: " . json_encode($header));
//            var_dump("\nResponse code: " . json_encode($responseCode));
//            var_dump('===============================================');

            // curl_close() has no effect on PHP 8.0+ when using CurlHandle objects.
            // Keep this call only for PHP < 8.0, where it still closes the cURL resource handle.
            // @link https://www.php.net/manual/en/function.curl-close.php
            if (PHP_VERSION_ID < 80000) {
                curl_close($ch);
            }

            if ($this->isSuccessStatusCode($responseCode)) {
                return true;
            }

            if ($this->canRetryStatusCode($responseCode, $retries)) {
                $retries++;
                usleep($this->retryDelayInMicroseconds($retries, $responseHeaders));
                continue;
            }

            // log error
            $this->handleError($responseCode, (string)$responseContent);
            return false;
        }
    }
}
