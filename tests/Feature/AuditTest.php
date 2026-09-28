<?php

declare(strict_types=1);

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Projecthanif\RouteScope\Audit\Auditor;
use Projecthanif\RouteScope\Audit\Issue;
use Projecthanif\RouteScope\Audit\Rules\ApiWithoutAuth;
use Projecthanif\RouteScope\Audit\Rules\DuplicateName;
use Projecthanif\RouteScope\Audit\Rules\MissingAction;
use Projecthanif\RouteScope\Audit\Rules\OverriddenRoute;
use Projecthanif\RouteScope\Audit\Rules\ShadowedRoute;
use Projecthanif\RouteScope\Audit\Severity;
use Projecthanif\RouteScope\Services\RouteScopeService;
use Projecthanif\RouteScope\Tests\Fixtures\AlwaysFailsRule;
use Projecthanif\RouteScope\Tests\Fixtures\PostController;
use Projecthanif\RouteScope\Tests\Fixtures\ShowDashboard;

beforeEach(function (): void {
    Route::setRoutes(new RouteCollection);
});

/**
 * @param  class-string  $rule
 * @return list<string> "rule: METHODS uri — message" for each issue
 */
function issuesFrom(string $rule): array
{
    config(['routescope.audit.rules' => [$rule]]);

    return app(Auditor::class)->audit()
        ->map(fn (Issue $issue): string => "{$issue->rule}: ".implode('|', $issue->route->methods)." {$issue->route->uri} — {$issue->message}")
        ->all();
}

it('flags controllers and methods that do not exist', function (): void {
    Route::get('missing-class', 'App\Http\Controllers\MissingController@index');
    Route::get('missing-method', PostController::class.'@show');
    Route::get('ok', PostController::class.'@index');
    Route::get('invokable', ShowDashboard::class);
    Route::get('closure', fn (): string => '');
    Route::view('about', 'about');
    Route::redirect('old', '/new');

    expect(issuesFrom(MissingAction::class))->toBe([
        'missing-action: GET /missing-class — Controller [App\Http\Controllers\MissingController] does not exist.',
        'missing-action: GET /missing-method — Method ['.PostController::class.'::show] does not exist.',
    ]);
});

it('flags routes replaced by a later definition of the same uri', function (): void {
    Route::get('posts', fn (): string => 'first');
    Route::match(['get', 'post'], 'posts', fn (): string => 'second');
    Route::get('users', fn (): string => '');
    Route::post('users', fn (): string => '');

    expect(issuesFrom(OverriddenRoute::class))->toBe([
        'overridden-route: GET /posts — GET /posts is replaced by a later definition of the same URI and never runs for GET.',
    ]);

    $issue = app(Auditor::class)->audit()->first();
    expect($issue->related[0]->methods)->toBe(['GET', 'POST']);
});

it('flags routes caught by an earlier route', function (): void {
    Route::get('users/{user}', fn (): string => '');
    Route::get('users/create', fn (): string => '');
    Route::get('posts/{post}', fn (): string => '')->whereNumber('post');
    Route::get('posts/create', fn (): string => '');
    Route::get('teams/{team}/{tab?}', fn (): string => '');
    Route::get('teams/{team}', fn (): string => '');
    Route::put('users/{id}', fn (): string => '');
    Route::put('users/{user}', fn (): string => '');
    Route::fallback(fn (): string => '');

    expect(issuesFrom(ShadowedRoute::class))->toBe([
        'shadowed-route: GET /teams/{team} — GET /teams/{team} is caught by /teams/{team}/{tab?}, which Laravel checks first.',
        'shadowed-route: GET /users/create — GET /users/create is caught by /users/{user}, which Laravel checks first.',
        'shadowed-route: PUT /users/{user} — PUT /users/{user} is caught by /users/{id}, which Laravel checks first.',
    ]);
});

it('builds sample requests that satisfy where() constraints and domains', function (): void {
    Route::get('files/{name}', fn (): string => '');
    Route::get('files/{slug}', fn (): string => '')->where('slug', '[a-z]+');
    Route::get('codes/{code}', fn (): string => '')->where('code', '[A-Z]{3}');
    Route::domain('{account}.example.com')->group(function (): void {
        Route::get('home', fn (): string => '');
    });
    Route::domain('{tenant}.example.com')->group(function (): void {
        Route::get('home', fn (): string => '');
    });
    Route::domain('{id}.example.com')->group(function (): void {
        Route::get('dash', fn (): string => '');
    })->where('id', '[A-Z]+');

    expect(issuesFrom(ShadowedRoute::class))->toBe([
        'shadowed-route: GET /files/{slug} — GET /files/{slug} is caught by /files/{name}, which Laravel checks first.',
        'shadowed-route: GET /home — GET /home is caught by /home, which Laravel checks first.',
    ]);
});

it('flags duplicate route names', function (): void {
    Route::get('a', fn (): string => '')->name('dupe');
    Route::get('b', fn (): string => '')->name('dupe');
    Route::get('c', fn (): string => '')->name('unique');
    Route::get('d', fn (): string => '');

    expect(issuesFrom(DuplicateName::class))->toBe([
        'duplicate-name: GET /a — Route name [dupe] is also used by /b.',
        'duplicate-name: GET /b — Route name [dupe] is also used by /a.',
    ]);
});

it('flags api routes without authentication', function (): void {
    app('router')->aliasMiddleware('custom-auth', 'App\Http\Middleware\CustomAuth');

    Route::get('api/open', fn (): string => '')->middleware('api');
    Route::get('api/sanctum', fn (): string => '')->middleware(['api', 'auth:sanctum']);
    Route::get('api/signed', fn (): string => '')->middleware(['api', 'signed']);
    Route::get('api/basic', fn (): string => '')->middleware('auth.basic');
    Route::get('api/custom', fn (): string => '')->middleware('custom-auth');
    Route::get('web/open', fn (): string => '');

    expect(issuesFrom(ApiWithoutAuth::class))->toBe([
        'api-without-auth: GET /api/custom — API route has no authentication middleware.',
        'api-without-auth: GET /api/open — API route has no authentication middleware.',
    ]);

    config(['routescope.audit.auth_middleware' => ['App\Http\Middleware\CustomAuth', 'auth']]);

    expect(issuesFrom(ApiWithoutAuth::class))->toBe([
        'api-without-auth: GET /api/basic — API route has no authentication middleware.',
        'api-without-auth: GET /api/open — API route has no authentication middleware.',
        'api-without-auth: GET /api/signed — API route has no authentication middleware.',
    ]);

    config(['routescope.audit.auth_middleware' => 'invalid']);

    expect(issuesFrom(ApiWithoutAuth::class))->toHaveCount(2);
});

it('ignores issues by uri pattern or route name, per rule or for all rules', function (): void {
    Route::get('api/login', fn (): string => '')->name('login');
    Route::get('api/webhooks/stripe', fn (): string => '');
    Route::get('api/users', fn (): string => '');
    Route::get('api/posts', fn (): string => '');

    config(['routescope.audit.ignore' => [
        'api-without-auth' => ['login', '/api/webhooks/*'],
        '*' => ['api/posts'],
        'duplicate-name' => ['api/users'],
    ]]);

    expect(issuesFrom(ApiWithoutAuth::class))->toBe([
        'api-without-auth: GET /api/users — API route has no authentication middleware.',
    ]);

    config(['routescope.audit.ignore' => 'invalid']);

    expect(issuesFrom(ApiWithoutAuth::class))->toHaveCount(4);
});

it('runs all default rules and sorts errors first', function (): void {
    Route::get('api/open', fn (): string => '');
    Route::get('broken', 'App\Http\Controllers\MissingController@index');

    $issues = app(Auditor::class)->audit();

    expect($issues->map(fn (Issue $issue): string => $issue->rule)->all())->toBe(['missing-action', 'api-without-auth'])
        ->and($issues->first()->severity)->toBe(Severity::Error)
        ->and($issues->first()->toArray())->toBe([
            'rule' => 'missing-action',
            'severity' => 'error',
            'message' => 'Controller [App\Http\Controllers\MissingController] does not exist.',
            'route' => 'GET /broken',
            'related' => [],
        ]);
});

it('supports custom rules and rejects invalid ones', function (): void {
    Route::get('a', fn (): string => '');

    config(['routescope.audit.rules' => [AlwaysFailsRule::class]]);
    expect(app(Auditor::class)->audit()->first()->message)->toBe('Custom rule.');

    config(['routescope.audit.rules' => [new AlwaysFailsRule(app(RouteScopeService::class))]]);
    expect(app(Auditor::class)->audit())->toHaveCount(1);

    config(['routescope.audit.rules' => 'invalid']);
    expect(app(Auditor::class)->audit())->toHaveCount(0);

    config(['routescope.audit.rules' => [stdClass::class]]);
    app(Auditor::class)->audit();
})->throws(InvalidArgumentException::class, 'Audit rules must implement');

it('reports no issues and succeeds when routes are clean', function (): void {
    Route::get('about', fn (): string => '');

    $this->artisan('routescope:audit')
        ->expectsOutputToContain('No route issues found.')
        ->assertSuccessful();
});

it('lists issues and fails on warnings by default', function (): void {
    Route::get('api/open', fn (): string => '');
    Route::get('broken', 'App\Http\Controllers\MissingController@index');

    $this->artisan('routescope:audit')
        ->expectsOutputToContain('GET /broken')
        ->expectsOutputToContain('Controller [App\Http\Controllers\MissingController] does not exist.')
        ->expectsOutputToContain('GET /api/open')
        ->expectsOutputToContain('1 error, 1 warning')
        ->assertFailed();
});

it('pluralizes the summary', function (): void {
    Route::get('api/a', fn (): string => '');
    Route::get('api/b', fn (): string => '');

    $this->artisan('routescope:audit')
        ->expectsOutputToContain('0 errors, 2 warnings')
        ->assertFailed();
});

it('respects the fail-on threshold', function (string $failOn, array $route, int $exitCode): void {
    Route::get(...$route);

    $this->artisan('routescope:audit', ['--fail-on' => $failOn])->assertExitCode($exitCode);
})->with([
    'warning only, fail on error' => ['error', ['api/open', fn (): string => ''], 0],
    'error, fail on error' => ['error', ['broken', 'App\Http\Controllers\Missing@index'], 1],
    'warning, never fail' => ['never', ['api/open', fn (): string => ''], 0],
]);

it('rejects an invalid fail-on value', function (): void {
    $this->artisan('routescope:audit', ['--fail-on' => 'sometimes'])
        ->expectsOutputToContain('The --fail-on option must be')
        ->assertExitCode(2);
});

it('outputs json', function (): void {
    Route::get('api/open', fn (): string => '');

    $this->artisan('routescope:audit', ['--json' => true, '--fail-on' => 'never'])
        ->expectsOutputToContain('"route": "GET /api/open"')
        ->assertSuccessful();
});
