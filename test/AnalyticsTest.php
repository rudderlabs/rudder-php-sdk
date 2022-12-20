<?php

declare(strict_types=1);

namespace Rudder\Test;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Rudder\Rudder;
use Rudder\RudderException;

class AnalyticsTest extends TestCase
{
    public function setUp(): void
    {
        // Looking for .env at the root directory
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Retrieve env variables
        $__WRITE_KEY__ = $_ENV['WRITE_KEY'];
        $__DATAPLANE_URL__ = $_ENV['DATAPLANE_URL'];

        date_default_timezone_set('UTC');
        Rudder::init($__WRITE_KEY__, ['debug' => true, 'data_plane_url' => $__DATAPLANE_URL__]);
    }

    public function testTrack(): void
    {
        self::assertTrue(
            Rudder::track(
                [
                    'userId' => 'john',
                    'event'  => 'Module PHP Event',
                ]
            )
        );
    }

    public function testGroup(): void
    {
        self::assertTrue(
            Rudder::group(
                [
                    'groupId' => 'group-id',
                    'userId'  => 'user-id',
                    'traits'  => [
                        'plan' => 'startup',
                    ],
                ]
            )
        );
    }

    public function testGroupAnonymous(): void
    {
        self::assertTrue(
            Rudder::group(
                [
                    'groupId'     => 'group-id',
                    'anonymousId' => 'anonymous-id',
                    'traits'      => [
                        'plan' => 'startup',
                    ],
                ]
            )
        );
    }

    public function testGroupNoUser(): void
    {
        $this->expectExceptionMessage('Rudder::group() requires userId or anonymousId');
        $this->expectException(RudderException::class);
        Rudder::group(
            [
                'groupId' => 'group-id',
                'traits'  => [
                    'plan' => 'startup',
                ],
            ]
        );
    }

    public function testMicrotime(): void
    {
        self::assertTrue(
            Rudder::page(
                [
                    'anonymousId' => 'anonymous-id',
                    'name'        => 'analytics-php-microtime',
                    'category'    => 'docs',
                    'timestamp'   => microtime(true),
                    'properties'  => [
                        'path' => '/docs/libraries/php/',
                        'url'  => 'https://docs.rudderstack.com',
                    ],
                ]
            )
        );
    }

    public function testPage(): void
    {
        self::assertTrue(
            Rudder::page(
                [
                    'anonymousId' => 'anonymous-id',
                    'name'        => 'analytics-php',
                    'category'    => 'docs',
                    'properties'  => [
                        'path' => '/docs/libraries/php/',
                        'url'  => 'https://docs.rudderstack.com',
                    ],
                ]
            )
        );
    }

    public function testBasicPage(): void
    {
        self::assertTrue(Rudder::page(['anonymousId' => 'anonymous-id']));
    }

    public function testScreen(): void
    {
        self::assertTrue(
            Rudder::screen(
                [
                    'anonymousId' => 'anonymous-id',
                    'name'        => '2048',
                    'category'    => 'game built with php :)',
                    'properties'  => [
                        'points' => 300,
                    ],
                ]
            )
        );
    }

    public function testBasicScreen(): void
    {
        self::assertTrue(Rudder::screen(['anonymousId' => 'anonymous-id']));
    }

    public function testIdentify(): void
    {
        self::assertTrue(
            Rudder::identify(
                [
                    'userId' => 'doe',
                    'traits' => [
                        'loves_php' => false,
                        'birthday'  => time(),
                    ],
                ]
            )
        );
    }

    public function testEmptyTraits(): void
    {
        self::assertTrue(Rudder::identify(['userId' => 'empty-traits']));

        self::assertTrue(
            Rudder::group(
                [
                    'userId'  => 'empty-traits',
                    'groupId' => 'empty-traits',
                ]
            )
        );
    }

    public function testEmptyArrayTraits(): void
    {
        self::assertTrue(
            Rudder::identify(
                [
                    'userId' => 'empty-traits',
                    'traits' => [],
                ]
            )
        );

        self::assertTrue(
            Rudder::group(
                [
                    'userId'  => 'empty-traits',
                    'groupId' => 'empty-traits',
                    'traits'  => [],
                ]
            )
        );
    }

    public function testEmptyProperties(): void
    {
        self::assertTrue(
            Rudder::track(
                [
                    'userId' => 'user-id',
                    'event'  => 'empty-properties',
                ]
            )
        );

        self::assertTrue(
            Rudder::page(
                [
                    'category' => 'empty-properties',
                    'name'     => 'empty-properties',
                    'userId'   => 'user-id',
                ]
            )
        );
    }

    public function testEmptyArrayProperties(): void
    {
        self::assertTrue(
            Rudder::track(
                [
                    'userId'     => 'user-id',
                    'event'      => 'empty-properties',
                    'properties' => [],
                ]
            )
        );

        self::assertTrue(
            Rudder::page(
                [
                    'category'   => 'empty-properties',
                    'name'       => 'empty-properties',
                    'userId'     => 'user-id',
                    'properties' => [],
                ]
            )
        );
    }

    public function testAlias(): void
    {
        self::assertTrue(
            Rudder::alias(
                [
                    'previousId' => 'previous-id',
                    'userId'     => 'user-id',
                ]
            )
        );
    }

    public function testContextEmpty(): void
    {
        self::assertTrue(
            Rudder::track(
                [
                    'userId'  => 'user-id',
                    'event'   => 'Context Test',
                    'context' => [],
                ]
            )
        );
    }

    public function testContextCustom(): void
    {
        self::assertTrue(
            Rudder::track(
                [
                    'userId'  => 'user-id',
                    'event'   => 'Context Test',
                    'context' => ['active' => false],
                ]
            )
        );
    }

    public function testTimestamps(): void
    {
        self::assertTrue(
            Rudder::track(
                [
                    'userId'    => 'user-id',
                    'event'     => 'integer-timestamp',
                    'timestamp' => (int)mktime(0, 0, 0, (int)date('n'), 1, (int)date('Y')),
                ]
            )
        );

        self::assertTrue(
            Rudder::track(
                [
                    'userId'    => 'user-id',
                    'event'     => 'string-integer-timestamp',
                    'timestamp' => (string)mktime(0, 0, 0, (int)date('n'), 1, (int)date('Y')),
                ]
            )
        );

        self::assertTrue(
            Rudder::track(
                [
                    'userId'    => 'user-id',
                    'event'     => 'iso8630-timestamp',
                    'timestamp' => date(DATE_ATOM, mktime(0, 0, 0, (int)date('n'), 1, (int)date('Y'))),
                ]
            )
        );

        self::assertTrue(
            Rudder::track(
                [
                    'userId'    => 'user-id',
                    'event'     => 'iso8601-timestamp',
                    'timestamp' => date(DATE_ATOM, mktime(0, 0, 0, (int)date('n'), 1, (int)date('Y'))),
                ]
            )
        );

        self::assertTrue(
            Rudder::track(
                [
                    'userId'    => 'user-id',
                    'event'     => 'strtotime-timestamp',
                    'timestamp' => strtotime('1 week ago'),
                ]
            )
        );

        self::assertTrue(
            Rudder::track(
                [
                    'userId'    => 'user-id',
                    'event'     => 'microtime-timestamp',
                    'timestamp' => microtime(true),
                ]
            )
        );

        self::assertTrue(
            Rudder::track(
                [
                    'userId'    => 'user-id',
                    'event'     => 'invalid-float-timestamp',
                    'timestamp' => ((string)mktime(0, 0, 0, (int)date('n'), 1, (int)date('Y'))) . '.',
                ]
            )
        );
    }
}
