<?php

declare(strict_types=1);

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Projecthanif\RouteScope\Facades\RouteScope;
use Projecthanif\RouteScope\Tests\Fixtures\ShowDashboard;

beforeEach(function (): void {
    Route::setRoutes(new RouteCollection);
});

/**
 * @return list<array<string, mixed>>
 */
function allRoutes(): array
{
    $routes = RouteScope::getAllRoutes();

    return [...$routes['apiRoutes']->all(), ...$routes['webRoutes']->all()];
}

function sourceFor(string $path): string
{
    return collect(allRoutes())->firstWhere('path', $path)['source'];
}

it('formats routes', function (): void {
    Route::get('users', 'App\Http\Controllers\UserController@index')
        ->name('users.index')
        ->middleware(['web', 'auth']);

    expect(RouteScope::getAllRoutes()['webRoutes']->all())->toBe([
        [
            'method' => 'GET',
            'path' => '/users',
            'source' => 'http/controllers/UserController::index',
            'name' => 'users.index',
            'middleware' => ['web', 'auth'],
        ],
    ]);
});

it('splits api and web routes by prefix and middleware group', function (): void {
    Route::get('api', fn (): string => '');
    Route::get('api/users', fn (): string => '');
    Route::get('v1/orders', fn (): string => '')->middleware('api');
    Route::get('apiary', fn (): string => '');
    Route::get('/', fn (): string => '');

    $routes = RouteScope::getAllRoutes();

    expect($routes['apiRoutes']->pluck('path')->all())->toBe(['/api', '/api/users', '/v1/orders'])
        ->and($routes['webRoutes']->pluck('path')->all())->toBe(['/', '/apiary']);
});

it('hides HEAD and OPTIONS and sorts by path then method', function (): void {
    Route::delete('posts', fn (): string => '');
    Route::addRoute('PURGE', 'posts', fn (): string => '');
    Route::post('posts', fn (): string => '');
    Route::get('posts', fn (): string => '');
    Route::options('posts', fn (): string => '');
    Route::get('comments', fn (): string => '');

    expect(collect(allRoutes())->map(fn (array $r): string => $r['method'].' '.$r['path'])->all())->toBe([
        'GET /comments',
        'GET /posts',
        'POST /posts',
        'DELETE /posts',
        'PURGE /posts',
    ]);
});

it('excludes matching paths and everything beneath them', function (): void {
    config(['routescope.excluded_patterns' => ['telescope', '/admin/*/debug/']]);

    Route::get('telescope', fn (): string => '');
    Route::get('telescope/requests', fn (): string => '');
    Route::get('telescopes', fn (): string => '');
    Route::get('shop/telescope', fn (): string => '');
    Route::get('admin/users/debug', fn (): string => '');
    Route::get('admin/users/debug/sql', fn (): string => '');
    Route::get('admin/users', fn (): string => '');

    expect(collect(allRoutes())->pluck('path')->all())
        ->toBe(['/admin/users', '/shop/telescope', '/telescopes']);
});

it('ignores an invalid excluded_patterns config', function (): void {
    config(['routescope.excluded_patterns' => 'telescope']);

    Route::get('telescope', fn (): string => '');

    expect(collect(allRoutes())->pluck('path')->all())->toBe(['/telescope']);
});

it('always hides its own routes', function (): void {
    config(['routescope.prefix' => 'custom-scope']);
    $this->rebootPackage();

    Route::get('users', fn (): string => '');

    expect(collect(allRoutes())->pluck('path')->all())->toBe(['/users']);
});

it('only keeps string middleware', function (): void {
    Route::get('users', fn (): string => '')->middleware(['auth', 'throttle:60,1']);

    expect(allRoutes()[0]['middleware'])->toBe(['auth', 'throttle:60,1']);
});

it('describes route sources', function (): void {
    Route::get('closure', fn (): string => '');
    Route::get('invokable', ShowDashboard::class);
    Route::get('fqcn', '\App\Http\Controllers\PostController@show');
    Route::get('deep', 'App\Http\Controllers\Admin\Reports\Quarterly\ExportController@index');
    Route::get('vendor', 'Vendor\Package\Http\WidgetController@index');
    Route::get('global', 'GlobalController@index');
    Route::view('about', 'pages.about');
    Route::redirect('old', '/new');

    expect(sourceFor('/closure'))->toBe('Closure')
        ->and(sourceFor('/invokable'))->toBe('projecthanif/.../fixtures/ShowDashboard::__invoke')
        ->and(sourceFor('/fqcn'))->toBe('http/controllers/PostController::show')
        ->and(sourceFor('/deep'))->toBe('http/.../quarterly/ExportController::index')
        ->and(sourceFor('/vendor'))->toBe('vendor/package/http/WidgetController::index')
        ->and(sourceFor('/global'))->toBe('app/GlobalController::index')
        ->and(sourceFor('/about'))->toBe('View: pages.about')
        ->and(sourceFor('/old'))->toBe('Redirect: /new');
});

it('reports closures from cached routes as closures', function (): void {
    Route::get('cached', fn (): string => '');

    // Cached routes store closures as serialized strings, not Closure instances.
    $route = Route::getRoutes()->getRoutes()[0];
    $route->setAction(array_merge($route->getAction(), ['uses' => 'O:47:"Laravel\SerializableClosure\SerializableClosure":0:{}']));

    expect(sourceFor('/cached'))->toBe('Closure');
});
