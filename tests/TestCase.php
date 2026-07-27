<?php
declare(strict_types=1);

namespace Refatbd\LaravelFreeFire\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Refatbd\LaravelFreeFire\FreeFireServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [FreeFireServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('freefire.routes.enabled', true);
        $app['config']->set('freefire.routes.compatibility', true);
        $app['config']->set('freefire.media.compatibility_routes', true);
        $app['config']->set('freefire.default_region', 'BD');
    }
}
