# RouteScope

**A powerful route inspection tool for Laravel developers.**

RouteScope gives you instant visibility into your application's routing layer. Stop guessing which routes exist, what middleware they use, or where they're defined. See everything at a glance with an elegant dashboard or query routes programmatically.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/projecthanif/routescope.svg?style=flat-square)](https://packagist.org/packages/projecthanif/routescope)
[![Total Downloads](https://img.shields.io/packagist/dt/projecthanif/routescope.svg?style=flat-square)](https://packagist.org/packages/projecthanif/routescope)

## Why RouteScope?

### 🔍 **Instant Route Visibility**
Ever wondered "Does this route actually exist?" or "What middleware is protecting this endpoint?" RouteScope answers these questions instantly with a clean, organized view of every route in your application.

### 🎯 **Smart Organization**
Routes are automatically categorized into API and web routes, making it easy to understand your application's structure at a glance. No more scrolling through `php artisan route:list` output.

### 🚀 **Developer Productivity**
- **Debug faster** - Quickly identify routing issues and middleware conflicts
- **Onboard easier** - New team members can explore the API surface in minutes
- **Document better** - Generate route documentation programmatically
- **Refactor confidently** - See the full scope of changes when restructuring routes

### 🛡️ **Production-Safe**
Built with safety in mind. RouteScope automatically disables itself in production environments and can be installed as a dev dependency to keep your production builds lean.

## Installation

Install as a development dependency:

```bash
composer require projecthanif/routescope --dev
```

The package auto-registers via Laravel's service provider discovery. No additional setup required!

## Quick Start

Visit the dashboard in your browser:

```
http://localhost/routescope
```

That's it! You'll see all your routes organized, searchable, and ready to explore.

## Configuration

Need to customize? Publish the configuration file:

```bash
php artisan vendor:publish --tag=routescope-config
```

Edit `config/routescope.php`:

```php
return [
    // null (the default) enables it in local/development only
    'enabled' => env('ROUTESCOPE_ENABLED'),
    
    // Customize the dashboard URL
    'prefix' => env('ROUTESCOPE_PREFIX', 'routescope'),

    // Middleware applied to the dashboard
    'middleware' => ['web'],

    // Hide routes you don't want to see (debug tools, internal routes, etc.).
    // A pattern hides the path and everything beneath it ("telescope" hides
    // "telescope/requests" but not "telescopes") and supports `*` wildcards.
    // RouteScope's own routes are always hidden.
    'excluded_patterns' => [
        '_ignition',
        'sanctum/csrf-cookie',
        'telescope',
        '_debugbar',
        '_boost',
        '__execute-laravel-error-solution',
    ],
];
```

To customize the dashboard itself, publish the views:

```bash
php artisan vendor:publish --tag=routescope-views
```

## Authorization

In the `local` environment the dashboard is open. Anywhere else (e.g. staging with `ROUTESCOPE_ENABLED=true`) access is denied unless the `viewRouteScope` gate passes. Define it in a service provider:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewRouteScope', function ($user = null) {
    return in_array($user?->email, ['admin@example.com'], true);
});
```

## Features

### 📊 Interactive Dashboard
A beautiful, responsive interface that displays:
- HTTP methods (GET, POST, PUT, DELETE, PATCH)
- Route URIs and named routes (one row per route, with all of its methods)
- Controller actions or closure definitions
- Applied middleware
- Search across path, method, name, middleware and source
- Copy a path, or open parameter-free GET routes in a new tab

### 🔌 Programmatic Access
Query routes from your code using the facade or dependency injection. Each route is a typed `RouteData` object:

```php
use Projecthanif\RouteScope\Facades\RouteScope;

RouteScope::all();  // Collection<int, RouteData>
RouteScope::api();  // Routes under /api or using the "api" middleware group
RouteScope::web();  // All other routes
RouteScope::filter(fn (RouteData $route) => $route->hasMiddleware('auth'));
```

### 🎨 Smart Categorization
Routes are automatically organized:
- **API Routes**: Everything under `/api` or using the `api` middleware group
- **Web Routes**: Your standard web application routes

### ⚙️ Flexible Filtering
Exclude routes you don't care about:
- Debug tools (Telescope, Ignition)
- Internal Laravel routes
- Third-party package routes
- Custom patterns you define

### 🔒 Type-Safe
Built with strict typing and comprehensive type hints for a better development experience with IDE autocomplete and static analysis tools.

## Usage Examples

### View All Routes in a Custom Command

```php
use Projecthanif\RouteScope\Data\RouteData;
use Projecthanif\RouteScope\Facades\RouteScope;

class InspectRoutes extends Command
{
    public function handle()
    {
        foreach (RouteScope::all() as $route) {
            $this->line(sprintf('%-12s %s', implode('|', $route->methods), $route->uri));
        }
    }
}
```

### Find API Routes Without Authentication

```php
$unprotected = RouteScope::api()
    ->reject(fn (RouteData $route) => $route->hasMiddleware('auth') || $route->hasMiddleware('auth:sanctum'));
```

`hasMiddleware()` checks both the declared middleware and the resolved middleware (after expanding groups and aliases). It also matches parameterized middleware, so `hasMiddleware('throttle')` matches `throttle:60,1`.

### Generate API Documentation

```php
$markdown = "# API Endpoints\n\n";

foreach (RouteScope::api() as $route) {
    $markdown .= '### '.implode('|', $route->methods)." {$route->uri}\n\n";
    $markdown .= "**Controller**: `{$route->action}`\n";
    $markdown .= '**Middleware**: '.implode(', ', $route->middleware)."\n\n";
}

file_put_contents('api-docs.md', $markdown);
```

### Export as JSON

`RouteData` implements `JsonSerializable`, so collections can be returned or encoded directly:

```php
return response()->json(RouteScope::all());
```

### Dependency Injection

```php
use Projecthanif\RouteScope\Services\RouteScopeService;

class RouteAnalysisController extends Controller
{
    public function index(RouteScopeService $routeScope)
    {
        return view('admin.routes', ['routes' => $routeScope->all()]);
    }
}
```

## Auditing Routes

`routescope:audit` checks your routes for common mistakes:

| Rule | Severity | Catches |
|---|---|---|
| `missing-action` | error | Controller classes or methods that don't exist |
| `overridden-route` | warning | A route silently replaced by a later definition of the same method and URI |
| `shadowed-route` | warning | A route that never matches because an earlier one catches it (e.g. `/users/{user}` registered before `/users/create`) |
| `duplicate-name` | warning | Several routes sharing a name, so `route()` only reaches one |
| `api-without-auth` | warning | API routes without authentication middleware (signed URLs count as protected) |

```bash
php artisan routescope:audit
```

```
  WARN   POST /api/v1/auth/login  api-without-auth
         API route has no authentication middleware.

  0 errors, 1 warning
```

It exits with a non-zero code when it finds issues, so you can run it in CI:

```bash
php artisan routescope:audit                  # fail on warnings and errors (default)
php artisan routescope:audit --fail-on=error  # fail only on errors
php artisan routescope:audit --fail-on=never  # report only
php artisan routescope:audit --json           # machine-readable output
```

The dashboard shows the same issues on each route, with a header button to show only affected routes.

### Configuring the Audit

```php
'audit' => [
    // Middleware that counts as authentication. "auth" also matches "auth:sanctum".
    'auth_middleware' => ['auth', 'auth.basic', 'signed', /* ... */],

    // URI patterns or route names to skip, per rule, or "*" for every rule
    'ignore' => [
        'api-without-auth' => ['api/*/auth/login', 'api/*/auth/register', 'webhooks.*'],
        '*' => ['api/internal/*'],
    ],

    // Replace or extend the rules. Each implements Projecthanif\RouteScope\Audit\Rule.
    'rules' => [
        \Projecthanif\RouteScope\Audit\Rules\MissingAction::class,
        // ...
        \App\Audit\RequireRouteNames::class,
    ],
],
```

A custom rule returns `Issue` objects and can type-hint dependencies in its constructor:

```php
use Projecthanif\RouteScope\Audit\{Issue, Rule, Severity};
use Projecthanif\RouteScope\Services\RouteScopeService;

final class RequireRouteNames implements Rule
{
    public function __construct(private RouteScopeService $routes) {}

    public function id(): string
    {
        return 'unnamed-route';
    }

    public function check(): iterable
    {
        foreach ($this->routes->all() as $route) {
            if ($route->name === null) {
                yield new Issue($this->id(), Severity::Warning, 'Route has no name.', $route);
            }
        }
    }
}
```

## API Reference

### `RouteScope::all(): Collection<int, RouteData>`

Returns every route, sorted by URI and then by HTTP method. RouteScope's own routes and `excluded_patterns` are left out. `api()`, `web()` and `filter(callable)` return the same collection narrowed down.

### `RouteData`

| Property | Type | Example |
|---|---|---|
| `methods` | `list<string>` | `['GET', 'POST']` (never `HEAD`/`OPTIONS`) |
| `uri` | `string` | `/users/{user}` |
| `name` | `?string` | `users.show` |
| `domain` | `?string` | `{account}.example.com` |
| `action` | `string` | `App\Http\Controllers\UserController@show`, or `Closure` |
| `source` | `string` | `http/controllers/UserController::show`, `Closure`, `View: welcome`, `Redirect: /new` |
| `middleware` | `list<string>` | `['web', 'auth']`, as declared |
| `resolvedMiddleware` | `list<string>` | Groups and aliases expanded to classes |
| `parameters` | `list<RouteParameter>` | `name`, `optional`, and `pattern` (from `where()` constraints) |
| `file` / `line` | `?string` / `?int` | `app/Http/Controllers/UserController.php`, `42` (relative to the app when inside it) |
| `isApi` | `bool` | |
| `isFallback` | `bool` | |

Methods: `hasMethod(string)`, `hasMiddleware(string)`, `toArray()` (snake_case keys), `jsonSerialize()`.

### `RouteScope::getAllRoutes(): array` (deprecated)

The v2 format: `['apiRoutes' => Collection, 'webRoutes' => Collection]`, with one array per HTTP method (`method`, `path`, `name`, `source`, `middleware`). It still works in v3 and will be removed in v4. See [UPGRADE.md](UPGRADE.md).

## Environment Variables

```env
# Enable/disable the package (automatically disabled in production)
ROUTESCOPE_ENABLED=true

# Customize the dashboard URL prefix
ROUTESCOPE_PREFIX=routescope
```

## Production Safety

RouteScope is designed to be safe by default:

1. **Auto-disabled in production** - The default configuration only enables RouteScope in local/development environments
2. **Gated outside local** - When enabled elsewhere, the `viewRouteScope` gate must pass (see [Authorization](#authorization))
3. **Dev dependency** - Install with `--dev` to exclude from production builds
4. **Lightweight** - Zero runtime overhead when disabled
5. **No database** - Purely reads from Laravel's route collection

To ensure it's disabled in production, add to `.env.production`:

```env
ROUTESCOPE_ENABLED=false
```

## Requirements

- PHP 8.3 or higher
- Laravel 11, 12 or 13

## Use Cases

### 🐛 **Debugging**
"Why isn't my route working?" - See instantly if the route exists, what middleware is blocking it, and where it's defined.

### 📚 **Documentation**
Generate comprehensive route documentation for your team or API consumers programmatically.

### 👥 **Onboarding**
New developers can explore your application's API surface without diving into route files.

### 🔍 **Auditing**
Quickly identify which routes lack authentication, have duplicate definitions, or use deprecated middleware.

### 🏗️ **Refactoring**
When restructuring your application, see the full scope of route changes in one place.

## Testing

```bash
composer test           # Run all tests
composer test:lint      # Code style checks
composer test:types     # Static analysis
composer test:unit      # Unit tests
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](./CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email iamustapha213@gmail.com instead of using the issue tracker.

## Credits

- [Ibrahim Mustapha](https://github.com/projecthanif)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [LICENSE.md](./LICENSE.md) for more information.

---

**RouteScope** - See your routes clearly. Debug confidently. Build faster.
