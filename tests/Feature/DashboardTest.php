<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Projecthanif\RouteScope\Http\Middleware\Authorize;
use Projecthanif\RouteScope\Providers\RouteScopeProvider;

function setEnvironment(string $env): void
{
    app()['env'] = $env;
}

it('renders the dashboard in the local environment', function (): void {
    setEnvironment('local');
    Route::get('api/users', fn (): string => '')->name('users.index');
    Route::get('about', fn (): string => '');

    $this->get('/routescope')
        ->assertOk()
        ->assertViewIs('routescope::routescope')
        ->assertViewHas('apiRoutes', fn (array $routes): bool => array_column($routes, 'uri') === ['/api/users'])
        ->assertViewHas('webRoutes', fn (array $routes): bool => in_array('/about', array_column($routes, 'uri'), true));
});

it('escapes route data embedded in the page', function (): void {
    setEnvironment('local');
    Route::get('xss/{a}', fn (): string => '')->name('</script><script>alert(1)</script>');

    $this->get('/routescope')
        ->assertOk()
        ->assertDontSee('</script><script>alert(1)</script>', false);
});

it('denies access outside local when the gate is not defined', function (): void {
    setEnvironment('production');

    $this->get('/routescope')->assertForbidden();
});

it('denies access outside local when the gate fails', function (): void {
    setEnvironment('production');
    Gate::define('viewRouteScope', fn ($user = null): bool => false);

    $this->get('/routescope')->assertForbidden();
});

it('allows access outside local when the gate passes', function (): void {
    setEnvironment('staging');
    Gate::define('viewRouteScope', fn ($user = null): bool => true);

    $this->get('/routescope')->assertOk();
});

it('registers named routes under the configured prefix and middleware', function (): void {
    config(['routescope.prefix' => 'tools/routes', 'routescope.middleware' => 'web']);
    $this->rebootPackage();

    $route = Route::getRoutes()->getByName('routescope.index');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('tools/routes')
        ->and($route->middleware())->toBe(['web', Authorize::class]);
});

it('does not register routes when disabled', function (): void {
    config(['routescope.enabled' => false]);
    $this->rebootPackage();

    expect(Route::getRoutes()->getByName('routescope.index'))->toBeNull();
});

it('can load the config file before the application environment is known', function (): void {
    // Published config files are loaded before the "env" binding exists, so the file must not touch the app.
    $app = Container::getInstance();
    Container::setInstance(new Container);

    try {
        $config = require RouteScopeProvider::CONFIG_PATH;
    } finally {
        Container::setInstance($app);
    }

    expect($config['enabled'])->toBeNull();
});

it('is enabled by default only in local and development', function (string $env, bool $enabled): void {
    config(['routescope.enabled' => null]);
    setEnvironment($env);
    $this->rebootPackage();

    expect(Route::getRoutes()->getByName('routescope.index') !== null)->toBe($enabled);
})->with([
    ['local', true],
    ['development', true],
    ['staging', false],
    ['production', false],
]);

it('can be forced on or off regardless of environment', function (mixed $value, string $env, bool $enabled): void {
    config(['routescope.enabled' => $value]);
    setEnvironment($env);
    $this->rebootPackage();

    expect(Route::getRoutes()->getByName('routescope.index') !== null)->toBe($enabled);
})->with([
    [true, 'production', true],
    ['true', 'production', true],
    [false, 'local', false],
    ['false', 'local', false],
]);
