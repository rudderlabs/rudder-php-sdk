<?php

declare(strict_types=1);

namespace Rudder\Test;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Rudder\Rudder;
use Rudder\RudderException;

class RudderInitializationTest extends TestCase
{
    protected function setUp(): void
    {
        $client = new ReflectionProperty(Rudder::class, 'client');
        $client->setAccessible(true);
        $client->setValue(null);
    }

    public function testStaticCallsRequireInitialization(): void
    {
        $calls = [
            static fn () => Rudder::track([]),
            static fn () => Rudder::identify([]),
            static fn () => Rudder::group([]),
            static fn () => Rudder::page([]),
            static fn () => Rudder::screen([]),
            static fn () => Rudder::alias([]),
            static fn () => Rudder::flush(),
        ];

        foreach ($calls as $call) {
            try {
                $call();
                self::fail('Expected a RudderException before initialization.');
            } catch (RudderException $exception) {
                self::assertSame(
                    'Rudder::init() must be called before any other tracking method.',
                    $exception->getMessage()
                );
            }
        }
    }
}
