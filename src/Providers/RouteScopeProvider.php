<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Projecthanif\RouteScope\Console\AuditCommand;
use Projecthanif\RouteScope\Http\Middleware\Authorize;

final class RouteScopeProvider extends ServiceProvider
{
    public const string CONFIG_PATH = __DIR__.'/../../config/routescope.php';

    public const string VIEWS_PATH = __DIR__.'/../../resources/views';

    /**
     * Register services into the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'routescope');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(self::VIEWS_PATH, 'routescope');
        $this->registerPublishing();
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([AuditCommand::class]);
        }
    }

    /**
     * Register the publishable config and views.
     */
    private function registerPublishing(): void
    {
        $this->publishes(
            [
                self::CONFIG_PATH => config_path('routescope.php'),
            ],
            'routescope-config',
        );

        $this->publishes(
            [
                self::VIEWS_PATH => resource_path('views/vendor/routescope'),
            ],
            // "views" is kept for backwards compatibility with v2.0
            ['routescope-views', 'views'],
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

        $middleware = config('routescope.middleware', ['web']);

        Route::group(
            [
                'prefix' => config('routescope.prefix', 'routescope'),
                'as' => 'routescope.',
                'middleware' => [...(is_array($middleware) ? $middleware : [$middleware]), Authorize::class],
            ],
            fn () => $this->loadRoutesFrom(__DIR__.'/../routes/web.php'),
        );
    }

    /**
     * Check if the package is enabled.
     */
    private function isEnabled(): bool
    {
        $enabled = config('routescope.enabled');

        if ($enabled === null) {
            return in_array($this->app->environment(), ['local', 'development'], true);
        }

        return filter_var($enabled, FILTER_VALIDATE_BOOL);
    }
}
