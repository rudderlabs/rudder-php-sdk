<?php

require_once("lib/Rudder.php");

echo "Hello world\n";

$user = array(
  "email" => "test@test.com",
  "name"  => "test name"
);

class_alias('Rudder', 'Analytics');

Rudder::init("1q9fNQo7JPQdf1GlOtPJNht3YvB", array(
  "data_plane_url" => "http://23c638820040.ngrok.io",
  "consumer"       => "lib_curl", // fork_curl
  "debug"          => true,
  "max_queue_size" => 10000,
  "batch_size"     => 6
));

echo "Hello world2\n";
Rudder::identify(array(
  "userId" => "2sfjej334",
  "traits" => array(
    "email" => "test@test.com",
    "name" => "test name",
    "friends" => 25
  )
));

Rudder::track(array(
  "userId" => "f4ca124298",
  "event" => "Signed Up",
  "properties" => array(
    "plan" => "Enterprise"
  )
));

Rudder::track(array(
  "userId" => "f4ca124298",
  "event" => "Article Bookmarked",
  "properties" => array(
    "title" => "Snow Fall",
    "subtitle" => "The Avalanche at Tunnel Creek",
    "author" => "John Branch"
  )
));

Rudder::page(array(
  "userId" => "f4ca124298",
  "category" => "Docs",
  "name" => "PHP library",
  "properties" => array(
    "url" => "https://segment.com/libraries/php/"
  )
));

Rudder::group(array(
  "userId" => "2sfjej334",
  "groupId" => "2sfjej334erresd",
  "traits" => array(
    "email" => "test@test.com",
    "name" => "test name",
    "friends" => 25
  )
));

Rudder::alias(array(
  "previousId" => "previousId",
  "userId" => "2sfjej334",
));

Rudder::flush();

?>
