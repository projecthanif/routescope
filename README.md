# Laravel Route Lens

A lightweight Laravel package for inspecting and analyzing your application routes.

## Installation

```bash
composer require projecthanif/laravel-route-lens
```

The package auto-registers via Laravel's service provider discovery.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=laravel-route-lens-config
```

Edit `config/laravel-route-lens.php`:

```php
return [
    'enabled' => env('LARAVEL_ROUTE_LENS_ENABLED', true),
    'prefix' => env('LARAVEL_ROUTE_LENS_PREFIX', 'route-lens'),
    'excluded_patterns' => ['route-lens', '_ignition', 'sanctum/csrf-cookie', 'telescope'],
];
```

## Usage

### Access the Dashboard

Visit `http://localhost/route-lens` in your browser to view the interactive route dashboard.

### Using the Facade

```php
use Projecthanif\LaravelRouteLens\Facades\RouteLens;

// Get all routes (API and Web separated)
$routes = RouteLens::getAllRoutes();

// Returns:
// [
//     'apiRoutes' => [...],
//     'webRoutes' => [...]
// ]
```

### Dependency Injection

```php
use Projecthanif\LaravelRouteLens\Services\LaravelRouteLensService;

class MyController extends Controller
{
    public function __construct(private LaravelRouteLensService $routeLens) {}
    
    public function analyze()
    {
        return $this->routeLens->getAllRoutes();
    }
}
```

## Features

- **Route Inspection** - View all registered routes with HTTP methods, paths, controllers, and middleware
- **Route Categorization** - Automatically separates API routes (`/api/*`) from web routes
- **Excluded Patterns** - Configurable route exclusion (e.g., debug tools, internal routes)
- **Interactive Dashboard** - Beautiful Blade view for exploring routes
- **Type-Safe** - Strict typing and comprehensive type hints throughout

## Environment Variables

```env
LARAVEL_ROUTE_LENS_ENABLED=true
LARAVEL_ROUTE_LENS_PREFIX=route-lens
```

## Disable in Production

Add to your `.env.production`:

```env
LARAVEL_ROUTE_LENS_ENABLED=false
```

Or conditionally in config:

```php
'enabled' => !app()->isProduction(),
```

## Requirements

- PHP 8.1+
- Laravel 12.0+
- Illuminate/Support

## File Structure

```
src/
├── Controllers/
│   └── RouteLensController.php
├── Facades/
│   └── RouteLens.php
├── Providers/
│   └── LaravelRouteLensProvider.php
├── Services/
│   └── LaravelRouteLensService.php
└── routes/
    └── web.php

config/
└── laravel-route-lens.php

resources/views/
└── routelens.blade.php
```

## API Reference

### LaravelRouteLensService

#### `getAllRoutes(): array`

Returns all routes organized into API and Web categories.

```php
$service = app(LaravelRouteLensService::class);
$data = $service->getAllRoutes();

// Result:
[
    'apiRoutes' => [
        [
            'method' => 'GET',
            'path' => '/api/users',
            'name' => 'users.index',
            'controller' => 'App\Http\Controllers\UserController@index',
            'middleware' => ['api', 'auth:sanctum'],
            'domain' => null
        ]
    ],
    'webRoutes' => [
        [
            'method' => 'GET',
            'path' => '/dashboard',
            'name' => 'dashboard',
            'controller' => 'App\Http\Controllers\DashboardController@show',
            'middleware' => ['web', 'auth'],
            'domain' => null
        ]
    ]
]
```

## Route Details

Each route object contains:

| Field | Type | Description |
|-------|------|-------------|
| `method` | string | HTTP method (GET, POST, PUT, DELETE, etc.) |
| `path` | string | Route URI path |
| `name` | string | Route name (or "unnamed") |
| `controller` | string | Controller class and method |
| `middleware` | array | Applied middleware |
| `domain` | string\|null | Route domain if specified |

## Examples

### List All Routes in Console

```php
use Projecthanif\LaravelRouteLens\Facades\RouteLens;

$all = RouteLens::getAllRoutes();

foreach ($all['apiRoutes'] as $route) {
    echo "{$route['method']} {$route['path']}\n";
}

foreach ($all['webRoutes'] as $route) {
    echo "{$route['method']} {$route['path']}\n";
}
```

### Find Specific Routes

```php
use Projecthanif\LaravelRouteLens\Services\LaravelRouteLensService;

$service = app(LaravelRouteLensService::class);
$routes = $service->getAllRoutes();

// Find routes with "user" in the path
$userRoutes = array_merge(
    collect($routes['apiRoutes'])->filter(fn($r) => str_contains($r['path'], 'user'))->toArray(),
    collect($routes['webRoutes'])->filter(fn($r) => str_contains($r['path'], 'user'))->toArray()
);
```

### Generate Route Documentation

```php
$routes = RouteLens::getAllRoutes();

echo "# API Routes\n\n";
foreach ($routes['apiRoutes'] as $route) {
    echo "- `{$route['method']}` `{$route['path']}` → `{$route['controller']}`\n";
}
```

## Tips

1. **Development Only** - Use in development and staging, disable in production
2. **Exclude Sensitive Routes** - Add patterns to `excluded_patterns` if needed
3. **Cache Friendly** - The package doesn't cache routes; use Laravel's route cache
4. **No Database** - Pure inspection utility, doesn't interact with database
5. **Zero Configuration** - Works out of the box with sensible defaults

## Testing

```bash
composer test
composer test:lint
composer test:types
composer test:unit
```

## Contributing

See [CONTRIBUTING.md](./CONTRIBUTING.md)

## License

MIT - See [LICENSE.md](./LICENSE.md)

## Author

Ibrahim Mustapha (iamustapha213@gmail.com)