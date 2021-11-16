<?php
require "lib/Rudder.php";
echo "htdocs ";
Rudder::init("20cc3WeHIWqfVEbtLRgubbv9amQ", array(
    "data_plane_url" => "https://0dfd-2409-4060-e98-5b30-81e9-2361-b1c1-a16c.ngrok.io",
    "consumer"       => "fork_curl", // lib_curl
    "debug"          => true,
    "max_queue_size" => 10000,
    "batch_size"     => 6
  ));
  Rudder::identify(array(
    "userId" => "2sfjej334",
    "traits" => array(
      "email" => "test@test.com",
      "name" => "test name",
      "friends" => 25
    )
  ));
  

?>
