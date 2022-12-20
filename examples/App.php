<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Rudder\Rudder;

require_once realpath(__DIR__ . '/../vendor/autoload.php');

// Looking for .env at the root directory
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Retrieve env variables
$__WRITE_KEY__ = $_ENV['WRITE_KEY'];
$__DATAPLANE_URL__ = $_ENV['DATAPLANE_URL'];
$__CONSUMER__ = $_ENV['CONSUMER'];

echo "Starting App.php with\n\nconsumer: $__CONSUMER__ \n\nwrite key: $__WRITE_KEY__ \n\n";

Rudder::init(
    $__WRITE_KEY__,
    [
        'data_plane_url' => $__DATAPLANE_URL__,
        'consumer'       => $__CONSUMER__,
        'debug'          => true,
        'max_queue_size' => 10000,
        'flush_at'     => 1,
    ]
);

Rudder::identify([
    'userId' => '2sfjej334',
    'traits' => [
        'email' => 'test@test.com',
        'name' => 'test name',
        'friends' => 25,
    ],
]);

Rudder::identify([
    'userId' => '4567dfgbhnm',
    'traits' => [
        'email' => 'test@test.com',
        'name' => 'test name',
        'friends' => 25,
    ],
]);
