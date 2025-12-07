<?php

declare(strict_types=1);

namespace Projecthanif\LaravelRouteLens\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class LaravelRouteLensProvider extends ServiceProvider
{
    public const CONFIG_PATH = __DIR__.'/../../config/laravel-route-lens.php';

    /**
     * Register services into the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'laravel-route-lens');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->registerRoutes();
    }

    /**
     * Publish the configuration file.
     */
    private function publishConfig(): void
    {
        $this->publishes(
            [
                self::CONFIG_PATH => config_path('laravel-route-lens.php'),
            ],
            'laravel-route-lens-config',
        );
    }

    /**
     * Register package routes.
     */
    private function registerRoutes(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        Route::group(
            [
                'prefix' => config('laravel-route-lens.prefix', 'route-lens'),
                'namespace' => 'Projecthanif\\LaravelRouteLens\\Controllers',
            ],
            fn () => $this->loadRoutesFrom(__DIR__.'/../routes/web.php'),
        );

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'laravel-route-lens');

        $this->publishes(
            [
                __DIR__.'/../../resources/views' => resource_path(
                    'views/vendor/laravel-route-lens',
                ),
            ],
            'views',
        );
    }

    /**
     * Check if the package is enabled.
     */
    private function isEnabled(): bool
    {
        return (bool) config('laravel-route-lens.enabled', true);
    }
}
