# What is RudderStack?

[RudderStack](https://rudderstack.com/) is a **customer data pipeline** tool for collecting, routing and processing data
from your websites, apps, cloud tools, and data warehouse.

More information on RudderStack can be found [here](https://github.com/rudderlabs/rudder-server).

## Getting Started with PHP SDK

Install `rudder-php-sdk` using `composer`
```
git clone https://github.com/rudderlabs/rudder-php-sdk /my/app/folders/
```

## Initialize the ```Client```

```
use Rudder\Rudder;

require_once realpath(__DIR__ . '/vendor/autoload.php');

Rudder::init(WRITE_KEY, array(
  "data_plane_url" => DATA_PLANE_URL,
  "consumer"       => "lib_curl",
  "debug"          => true,
  "max_queue_size" => 10000,
  "batch_size"     => 100
));
```

## SDK Initialization options

Below parameters are optional and can be passed during SDK initialization.

| Name                    | Type     | Default value                   | Description                                                                                                                                                                                                                                                 |
|:------------------------|:---------|:--------------------------------|:------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `consumer`              | String   | `lib_curl`                      | To explicitly mark which consumer to use.                                                                                                                                                                                                                   |
| `data_plane_url`        | String   | `hosted.rudderlabs.com`         | The data plane URL.                                                                                                                                                                                                                                         |
| `debug`                 | Boolean  | `false`                         | Whether to log messages and wait for a response. Makes any queuing consumers non-async, defaults to false. This will make the library block until a response has been received from the API, so it is not recommended for production use.                   |
| `ssl`                   | Boolean  | `true`                          | Whether to use SSL for the connection.                                                                                                                                                                                                                      |
| `tls`                   | String   | `false`                         | Whether to use TLS instead of SSL for the socket connection.                                                                                                                                                                                                |
| `error_handler`         | Function | `function ($code, $message) {}` | A handler which will be called on errors to aid in debugging. A function to handle errors, particularly useful for debugging. Note that if debug mode is not specified, then the error_handler will only be called on connection level errors and timeouts. |
| `max_queue_size`        | Number   | `10000`                         | The max number of items in the queue            .                                                                                                                                                                                                           |
| `flush_at`              | Number   | `100`                           | How many items to send in a single curl request        .                                                                                                                                                                                                    |
| `timeout`               | Number   | `0.5`                           | The number of seconds to wait for the socket request to time out    .                                                                                                                                                                                       |
| `filename`              | String   | `/tmp/analytics.log`            | The location to write the log file when file consumer is selected.                                                                                                                                                                                          |
| `compress_request`      | Boolean  | `true`                          | Whether to use gzipped request payloads. This is supported for server versions 1.4.0 and above.                                                                                                                                                             |
| `flush_inteval`         | Number   | `10000`                         | Frequency in milliseconds to send data using flush method execution.                                                                                                                                                                                        |
| `curl_timeout`          | Number   | `0 (infinite)`                  | Set timeout for the curl connections.                                                                                                                                                                                                                       |
| `curl_connecttimeout`   | Number   | `300`                           | Set connect timeout for the curl connections.                                                                                                                                                                                                               |
| `max_item_size_bytes`   | Number   | `32000`                         | Set maximum message item size in bytes.                                                                                                                                                                                                                     |
| `max_queue_size_bytes`  | Number   | `512000`                        | Set maximum batch size in bytes.                                                                                                                                                                                                                            |
| `filepermissions`       | Number   | `0644`                          | Set the file permissions for file consumer.                                                                                                                                                                                                                 |


## Send Events

```
Rudder::track(array(
  "userId" => "f4ca124298",
  "event" => "Signed Up",
  "properties" => array(
    "plan" => "Enterprise"
  )
));
```

## Contact Us

If you come across any issues while configuring or using this SDK, feel free to start a conversation on our
[Slack](https://resources.rudderstack.com/join-rudderstack-slack) channel. We will be happy to help you.
