<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Rudder\Rudder;

require_once realpath(__DIR__ . '/vendor/autoload.php');

// Looking for .env at the root directory
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Retrieve env variables
$__WRITE_KEY__ = $_ENV['WRITE_KEY'];
$__DATAPLANE_URL__ = $_ENV['DATAPLANE_URL'];
$__CONSUMER__ = $_ENV['CONSUMER'];
$__SSL__ = (bool)$_ENV['SSL'];
$dummyContext = [
    'screen' => [
        'density' => 1,
        'height' => 1024,
        'width' => 1680,
    ],
    'os' => [
        'name' => 'macOS',
        'version' => '12.0.0',
    ],
    'locale' => 'en-US',
    'library' => [
        'name' => 'dummy',
        'version' => 'x.x.x',
    ],
];
$dummyTraits = [
    'name' => 'Name Username',
    'email' => 'name@website.com',
    'plan' => 'Free',
    'friends' => 21,
];

echo "Starting App.php with:\nconsumer: $__CONSUMER__ \nwrite key: $__WRITE_KEY__ \n\n";

try {
    Rudder::init(
        $__WRITE_KEY__,
        [
            // 'compress_request'      => false,
            // 'host'                  => 'dummy.com',
            'ssl'                   => $__SSL__,
            'data_plane_url'        => $__DATAPLANE_URL__,
            'consumer'              => $__CONSUMER__,
            'debug'                 => true,
            'flush_at'              => 7,
        ]
    );

    Rudder::track([
        'anonymousId' => '490729fb-f4d1-4fd0-a00b-823d986609b8',
        'event' => 'Track Item 456',
        'properties' => [
            'price' => 45,
            'currency' => 'USD',
            'productId' => 'Product-12345',
        ],
        'context' => $dummyContext,
    ]);

    Rudder::identify([
        'userId' => 'Test user 1',
        'traits' => $dummyTraits,
        'context' => $dummyContext,
    ]);

    Rudder::track([
        'userId' => 'Test user 1',
        'event' => 'Track Item 457',
        'properties' => [
            'price' => 50,
            'currency' => 'USD',
            'productId' => 'Product-123456',
        ],
        'context' => $dummyContext,
    ]);

    Rudder::group([
        'userId' => 'Test user 1',
        'groupId' => 'grp - 1',
        'context' => $dummyContext,
        'traits' => $dummyTraits,
    ]);

    Rudder::page([
        'userId' => 'Test user 1',
        'name' => 'page viewed 123',
        'context' => $dummyContext,
    ]);

    Rudder::alias([
        'userId' => 'Test user 2',
        'previousId' => 'Test user 1',
        'context' => $dummyContext,
    ]);

    Rudder::screen([
        'userId' => 'Test user 2',
        'name' => 'screen viewed',
        'context' => $dummyContext,
    ]);
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
