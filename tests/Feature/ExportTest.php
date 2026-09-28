<?php

declare(strict_types=1);

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Projecthanif\RouteScope\Export\MarkdownExporter;
use Projecthanif\RouteScope\Export\OpenApiExporter;
use Projecthanif\RouteScope\Facades\RouteScope;
use Projecthanif\RouteScope\Tests\Fixtures\PostController;

beforeEach(function (): void {
    Route::setRoutes(new RouteCollection);
});

/**
 * Run the export command and return everything it printed.
 *
 * @param  array<string, mixed>  $arguments
 */
function export(array $arguments = []): string
{
    expect(Artisan::call('routescope:export', $arguments))->toBe(0);

    return Artisan::output();
}

/**
 * @return array<string, mixed>
 */
function openApi(): array
{
    return json_decode((new OpenApiExporter('Test API', '2.0.0'))->export(RouteScope::all()), true, flags: JSON_THROW_ON_ERROR);
}

it('exports json to standard output by default', function (): void {
    Route::get('users/{user}', fn (): string => '')->name('users.show');

    expect(export())->toContain('"uri": "/users/{user}"')->toContain('"name": "users.show"');
});

it('writes to a file, creating directories', function (): void {
    Route::get('users', fn (): string => '');
    $path = sys_get_temp_dir().'/routescope-'.uniqid().'/nested/routes.json';

    $this->artisan('routescope:export', ['format' => 'json', '--output' => $path])
        ->expectsOutputToContain('Exported 1 routes to')
        ->assertSuccessful();

    expect(json_decode((string) file_get_contents($path), true)[0]['uri'])->toBe('/users');

    unlink($path);
});

it('filters api and web routes', function (string $only, array $uris): void {
    Route::get('api/users', fn (): string => '');
    Route::get('about', fn (): string => '');

    $path = sys_get_temp_dir().'/routescope-'.uniqid().'.json';
    $this->artisan('routescope:export', ['--only' => $only, '--output' => $path])->assertSuccessful();

    expect(array_column(json_decode((string) file_get_contents($path), true), 'uri'))->toBe($uris);

    unlink($path);
})->with([
    ['api', ['/api/users']],
    ['web', ['/about']],
    ['all', ['/about', '/api/users']],
]);

it('rejects unknown formats and filters', function (array $arguments, string $message): void {
    $this->artisan('routescope:export', $arguments)
        ->expectsOutputToContain($message)
        ->assertExitCode(2);
})->with([
    [['format' => 'yaml'], 'The format must be'],
    [['--only' => 'admin'], 'The --only option must be'],
]);

it('exports markdown tables per section', function (): void {
    Route::get('api/users', PostController::class.'@index')->name('users.index')->middleware(['api', 'auth:sanctum']);
    Route::match(['get', 'post'], 'contact', fn (): string => '')->middleware('web');
    Route::get('pipes', fn (): string => '')->name('a|b');

    expect(export(['format' => 'md']))
        ->toContain("## API Routes\n\n| Method | URI | Name | Action | Middleware |\n|---|---|---|---|---|\n")
        ->toContain('| GET | `/api/users` | `users.index` | `'.PostController::class.'@index` | `api`, `auth:sanctum` |')
        ->toContain('## Web Routes')
        ->toContain('| GET, POST | `/contact` |  | `Closure` | `web` |')
        ->toContain('`a\|b`');
});

it('exports markdown with no routes', function (): void {
    expect((new MarkdownExporter)->export(collect()))->toBe("# Routes\n\nNo routes.\n");
});

it('exports only api routes to openapi by default', function (): void {
    config(['app.name' => 'Bragbox']);
    Route::get('api/users', fn (): string => '');
    Route::get('about', fn (): string => '');

    expect(export(['format' => 'openapi']))
        ->toContain('"title": "Bragbox"')
        ->toContain('"/api/users"')
        ->not->toContain('"/about"');

    config(['app.name' => null]);

    expect(export(['format' => 'openapi']))->toContain('"title": "API"');
});

it('builds an openapi document', function (): void {
    Route::get('api/v1/users/{user}', fn (): string => '')->name('users.show')->middleware(['api', 'auth'])->whereNumber('user');
    Route::put('api/v1/users/{user}', fn (): string => '')->whereNumber('user');
    Route::get('api/files/{name}', fn (): string => '')->where('name', '[a-z]+');
    Route::domain('{account}.example.com')->get('api/home', fn (): string => '');

    expect(openApi())->toBe([
        'openapi' => '3.1.0',
        'info' => ['title' => 'Test API', 'version' => '2.0.0'],
        'paths' => [
            '/api/files/{name}' => [
                'get' => [
                    'operationId' => 'getApiFilesName',
                    'summary' => 'Closure',
                    'tags' => ['files'],
                    'parameters' => [['name' => 'name', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string', 'pattern' => '^(?:[a-z]+)$']]],
                    'responses' => ['200' => ['description' => 'Successful response']],
                ],
            ],
            '/api/home' => [
                'get' => [
                    'operationId' => 'getApiHome',
                    'summary' => 'Closure',
                    'tags' => ['home'],
                    'responses' => ['200' => ['description' => 'Successful response']],
                    'x-domain' => '{account}.example.com',
                ],
            ],
            '/api/v1/users/{user}' => [
                'get' => [
                    'operationId' => 'users.show',
                    'summary' => 'users.show',
                    'tags' => ['users'],
                    'parameters' => [['name' => 'user', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Successful response']],
                    'x-middleware' => ['api', 'auth'],
                ],
                'put' => [
                    'operationId' => 'putApiV1UsersUser',
                    'summary' => 'Closure',
                    'tags' => ['users'],
                    'parameters' => [['name' => 'user', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Successful response']],
                ],
            ],
        ],
    ]);
});

it('expands optional parameters into separate paths', function (): void {
    Route::get('teams/{team}/{tab?}/{section?}', fn (): string => '')->name('teams.show');

    $paths = openApi()['paths'];

    expect(array_keys($paths))->toBe(['/teams/{team}', '/teams/{team}/{tab}', '/teams/{team}/{tab}/{section}'])
        ->and(array_column($paths['/teams/{team}/{tab}']['get']['parameters'], 'name'))->toBe(['team', 'tab'])
        // A name can't be the id of several operations, so optional routes fall back to generated ids
        ->and(array_map(fn (array $path): string => $path['get']['operationId'], array_values($paths)))
        ->toBe(['getTeamsTeam', 'getTeamsTeamTab', 'getTeamsTeamTabSection']);
});

it('declares parameters embedded inside a path segment', function (): void {
    Route::get('files/{name}.{ext}', fn (): string => '')->where('ext', 'pdf|png');

    $parameters = openApi()['paths']['/files/{name}.{ext}']['get']['parameters'];

    expect($parameters)->toBe([
        ['name' => 'name', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
        ['name' => 'ext', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string', 'pattern' => '^(?:pdf|png)$']],
    ]);
});

it('keeps operation ids unique and skips unsupported methods and fallbacks', function (): void {
    Route::match(['get', 'post'], 'items', fn (): string => '')->name('items');
    Route::get('items-', fn (): string => '');
    Route::get('items_', fn (): string => '');
    Route::addRoute('PURGE', 'cache', fn (): string => '');
    Route::get('{version}', fn (): string => '');
    Route::fallback(fn (): string => '');

    $paths = openApi()['paths'];

    expect(array_keys($paths))->toBe(['/items', '/items-', '/items_', '/{version}'])
        ->and($paths['/items']['get']['operationId'])->toBe('getItems')
        ->and($paths['/items']['post']['operationId'])->toBe('postItems')
        ->and($paths['/items-']['get']['operationId'])->toBe('getItems2')
        ->and($paths['/items_']['get']['operationId'])->toBe('getItems3')
        ->and($paths['/{version}']['get']['tags'])->toBe(['default']);
});

it('exports an empty openapi document', function (): void {
    expect((new OpenApiExporter)->export(collect()))->toContain('"paths": {}');
});
