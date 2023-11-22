<?php

declare(strict_types=1);

namespace Rudder\Consumer;

class LibCurl extends QueueConsumer
{
    protected string $type = 'LibCurl';

    /**
     * Make a sync request to our API. If debug is
     * enabled, we wait for the response
     * and retry once to diminish impact on performance.
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

        $backoff = 100; // Set initial waiting time to 100ms

        while ($backoff < $this->maximum_backoff_duration) {
            // open connection
            $ch = curl_init();

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

            // retry failed requests just once to diminish impact on performance
            $responseContent = curl_exec($ch);

            $err = curl_error($ch);
            if ($err) {
                $this->handleError(curl_errno($ch), $err);
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

            //close connection
            curl_close($ch);

            if ($responseCode !== 200) {
                // log error
                $this->handleError($responseCode, $responseContent);

                if (($responseCode >= 500 && $responseCode <= 600) || $responseCode === 429) {
                    // If status code is greater than 500 and less than 600, it indicates server error
                    // Error code 429 indicates rate limited.
                    // Retry uploading in these cases.
                    usleep($backoff * 1000);
                    $backoff *= 2;
                } elseif ($responseCode >= 400) {
                    break;
                }
            } else {
                break; // no error
            }
        }

        return true;
    }
}
