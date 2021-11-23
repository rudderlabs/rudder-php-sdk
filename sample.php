<?php
require "lib/Rudder.php";
echo "htdocs ";
Rudder::init("WRITE-KEY", array(
    "data_plane_url" => "DATA-PLANE-URL",
    "consumer"       => "fork_curl", // lib_curl
    "debug"          => true,
    "max_queue_size" => 10000,
    "batch_size"     => 1
  ));
  Rudder::identify(array(
    "userId" => "2sfjej334",
    "traits" => array(
      "email" => "test@test.com",
      "name" => "test name",
      "friends" => 25
    )
  ));
  Rudder::identify(array(
    "userId" => "4567dfgbhnm",
    "traits" => array(
      "email" => "test@test.com",
      "name" => "test name",
      "friends" => 25
    )
  ));
  

?>
