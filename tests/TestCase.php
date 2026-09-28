<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Tests;

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;
use Projecthanif\RouteScope\Providers\RouteScopeProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Re-run the package's route registration with the current config,
     * starting from an empty route collection.
     */
    public function rebootPackage(): void
    {
        Route::setRoutes(new RouteCollection);

        (new RouteScopeProvider($this->app))->boot();

        Route::getRoutes()->refreshNameLookups();
    }

    protected function getPackageProviders($app): array
    {
        return [RouteScopeProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('routescope.enabled', true);
    }
}
