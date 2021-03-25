# What is RudderStack?

[RudderStack](https://rudderstack.com/) is a **customer data pipeline** tool for collecting, routing and processing data from your websites, apps, cloud tools, and data warehouse.

More information on RudderStack can be found [here](https://github.com/rudderlabs/rudder-server).

## Getting Started with PHP SDK

Install `rudder-sdk-php` using `composer`
```
git clone https://github.com/rudderlabs/rudder-sdk-php /my/app/folders/
```

## Initialize the ```Client```

```
require_once("/path/to/lib/Rudder.php");

Rudder::init(WRITE_KEY, array(
  "data_plane_url" => DATA_PLANE_URL,
  "consumer"       => "lib_curl", // fork_curl
  "debug"          => true,
  "max_queue_size" => 10000,
  "batch_size"     => 100
));
```

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

If you come across any issues while configuring or using this SDK, feel free to start a conversation on our [Slack](https://resources.rudderstack.com/join-rudderstack-slack) channel. We will be happy to help you.
