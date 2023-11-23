<?php

declare(strict_types=1);

namespace Rudder\Consumer;

class ForkCurl extends QueueConsumer
{
    protected string $type = 'ForkCurl';

    /**
     * Make an async request to our API. Fork a curl process, immediately send
     * to the API. If debug is enabled, we wait for the response.
     * @param array $messages array of all the messages to send
     * @return bool whether the request succeeded
     */
    public function flushBatch(array $messages): bool
    {
        $body = $this->payload($messages);
        $payload = json_encode($body);
        $isSingleEventBatch = count($messages) === 1;

        // Escape for shell usage.
        $payload = escapeshellarg($payload);
        $secret = escapeshellarg($this->secret);

        $path = '/v1/batch';
        $url = $this->protocol . $this->host . $path;

        $cmd = "curl -u $secret: -X POST -H 'Content-Type: application/json'";

        $tmpfname = '';
        if ($this->compress_request) {
            // Compress request to file
            $tmpfname = tempnam('/tmp', 'forkcurl_');
            $cmd2 = 'echo ' . $payload . ' | gzip > ' . $tmpfname;
            exec($cmd2, $output, $exit);

            if ($exit !== 0) {
                $this->handleError($exit, $output);
                return false;
            }

            $cmd .= " -H 'Content-Encoding: gzip'";

            $cmd .= " --data-binary '@" . $tmpfname . "'";
        } else {
            $cmd .= ' -d ' . $payload;
        }

        $cmd .= " '" . $url . "'";

        // Verify payload size is below 512KB
        if (strlen($payload) >= 500 * 1024) {
            $msg = 'Payload size is larger than 512KB';
            error_log('[Analytics][' . $this->type . '] ' . $msg);

            return false;
        }

        // Send user agent in the form of {library_name}/{library_version} as per RFC 7231.
        $library = $messages[0]['context']['library'];
        $libName = $library['name'];
        $libVersion = $library['version'];
        $cmd .= " -H 'User-Agent: $libName/$libVersion'";

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
                $cmd .= " -H 'AnonymousId: $encodedAnonymousId'";
            }
        }

        if (!$this->debug()) {
            $cmd .= ' > /dev/null 2>&1 &';
        }

        exec($cmd, $output, $exit);

//            Uncomment for local debugging
//            var_dump('===============================================');
//            var_dump("Request body:\n");
//            var_dump(json_encode($body));
//            var_dump("\nRequest command: " . json_encode($cmd));
//            var_dump("\nExit code: " . $exit);
//            var_dump('===============================================');

        if ($exit !== 0) {
            $this->handleError($exit, $output);
        }

        if ($tmpfname !== '') {
            unlink($tmpfname);
        }

        return $exit === 0;
    }
}
