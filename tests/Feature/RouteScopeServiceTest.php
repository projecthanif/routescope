<?php

declare(strict_types=1);

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Projecthanif\RouteScope\Data\RouteData;
use Projecthanif\RouteScope\Data\RouteParameter;
use Projecthanif\RouteScope\Facades\RouteScope;
use Projecthanif\RouteScope\Tests\Fixtures\ShowDashboard;

beforeEach(function (): void {
    Route::setRoutes(new RouteCollection);
});

function routeAt(string $uri): RouteData
{
    $route = RouteScope::all()->first(fn (RouteData $route): bool => $route->uri === $uri);

    expect($route)->toBeInstanceOf(RouteData::class);

    return $route;
}

it('returns one entry per route with all of its methods', function (): void {
    Route::addRoute(['PURGE', 'DELETE', 'GET', 'POST'], 'posts', fn (): string => '');

    expect(RouteScope::all())->toHaveCount(1)
        ->and(routeAt('/posts')->methods)->toBe(['GET', 'POST', 'DELETE', 'PURGE']);
});

it('skips routes that only respond to HEAD or OPTIONS', function (): void {
    Route::options('preflight', fn (): string => '');

    expect(RouteScope::all())->toBeEmpty();
});

it('sorts routes by uri and then by first method', function (): void {
    Route::post('b', fn (): string => '');
    Route::get('a', fn (): string => '');
    Route::delete('b', fn (): string => '')->name('b.delete');
    Route::get('b', fn (): string => '');

    expect(RouteScope::all()->map(fn (RouteData $r): string => $r->methods[0].' '.$r->uri)->all())
        ->toBe(['GET /a', 'GET /b', 'POST /b', 'DELETE /b']);
});

it('describes a route', function (): void {
    app()->setBasePath(dirname(__DIR__, 2));

    Route::get('dashboard/{team}/{tab?}', ShowDashboard::class)
        ->name('dashboard')
        ->domain('{account}.example.com')
        ->middleware(['web', 'auth'])
        ->whereNumber('team');

    expect(routeAt('/dashboard/{team}/{tab?}')->toArray())->toBe([
        'methods' => ['GET'],
        'uri' => '/dashboard/{team}/{tab?}',
        'name' => 'dashboard',
        'domain' => '{account}.example.com',
        'action' => ShowDashboard::class,
        'source' => 'projecthanif/.../fixtures/ShowDashboard::__invoke',
        'middleware' => ['web', 'auth'],
        'resolved_middleware' => ['web', 'auth'],
        'parameters' => [
            ['name' => 'team', 'optional' => false, 'pattern' => '[0-9]+'],
            ['name' => 'tab', 'optional' => true, 'pattern' => null],
        ],
        'file' => 'tests/Fixtures/ShowDashboard.php',
        'line' => 9,
        'is_api' => false,
        'is_fallback' => false,
    ]);
});

it('serializes to json', function (): void {
    Route::get('users/{user}', fn (): string => '');

    $json = json_decode((string) json_encode(RouteScope::all()), true);

    expect($json[0]['uri'])->toBe('/users/{user}')
        ->and($json[0]['parameters'])->toBe([['name' => 'user', 'optional' => false, 'pattern' => null]]);
});

it('locates closures by absolute path when outside the app', function (): void {
    Route::get('closure', fn (): string => '');

    expect(routeAt('/closure')->file)->toBe(__FILE__)
        ->and(routeAt('/closure')->line)->toBe(__LINE__ - 3);
});

it('has no location when the action cannot be reflected', function (): void {
    Route::get('missing', 'App\Http\Controllers\MissingController@index');
    Route::view('about', 'pages.about');
    Route::redirect('old', '/new');
    Route::get('cached', fn (): string => '');

    // Cached routes store closures as serialized strings, not Closure instances.
    $cached = Route::getRoutes()->getRoutes()[3];
    $cached->setAction(array_merge($cached->getAction(), ['uses' => 'O:47:"Laravel\SerializableClosure\SerializableClosure":0:{}']));

    foreach (['/missing', '/about', '/old', '/cached'] as $uri) {
        expect(routeAt($uri)->file)->toBeNull()
            ->and(routeAt($uri)->line)->toBeNull();
    }

    expect(routeAt('/missing')->action)->toBe('App\Http\Controllers\MissingController@index');
});

it('resolves middleware groups and aliases', function (): void {
    $router = app('router');
    $router->middlewareGroup('admin', ['auth', 'verified']);
    $router->aliasMiddleware('auth', 'App\Http\Middleware\Authenticate');

    Route::get('admin', fn (): string => '')->middleware(['admin', 'throttle:60,1']);

    expect(routeAt('/admin')->middleware)->toBe(['admin', 'throttle:60,1'])
        ->and(routeAt('/admin')->resolvedMiddleware)->toBe(['App\Http\Middleware\Authenticate', 'verified', 'throttle:60,1']);
});

it('flags fallback routes', function (): void {
    Route::fallback(fn (): string => '');

    expect(routeAt('/{fallbackPlaceholder}')->isFallback)->toBeTrue();
});

it('splits and filters routes', function (): void {
    Route::get('api/users', fn (): string => '');
    Route::get('v1/orders', fn (): string => '')->middleware('api');
    Route::get('about', fn (): string => '')->middleware('auth');

    expect(RouteScope::api()->pluck('uri')->all())->toBe(['/api/users', '/v1/orders'])
        ->and(RouteScope::web()->pluck('uri')->all())->toBe(['/about'])
        ->and(RouteScope::filter(fn (RouteData $r): bool => $r->hasMiddleware('auth'))->pluck('uri')->all())->toBe(['/about']);
});

it('checks methods and middleware on a route', function (): void {
    $route = new RouteData(
        methods: ['GET', 'POST'],
        uri: '/users',
        name: null,
        domain: null,
        action: 'Closure',
        source: 'Closure',
        middleware: ['web', 'throttle:60,1'],
        resolvedMiddleware: ['App\Http\Middleware\EncryptCookies'],
        parameters: [new RouteParameter('user', false, null)],
        file: null,
        line: null,
        isApi: false,
        isFallback: false,
    );

    expect($route->hasMethod('post'))->toBeTrue()
        ->and($route->hasMethod('DELETE'))->toBeFalse()
        ->and($route->hasMiddleware('web'))->toBeTrue()
        ->and($route->hasMiddleware('throttle'))->toBeTrue()
        ->and($route->hasMiddleware('throttle:60,1'))->toBeTrue()
        ->and($route->hasMiddleware('App\Http\Middleware\EncryptCookies'))->toBeTrue()
        ->and($route->hasMiddleware('thro'))->toBeFalse()
        ->and($route->parameters[0]->jsonSerialize())->toBe(['name' => 'user', 'optional' => false, 'pattern' => null]);
});
