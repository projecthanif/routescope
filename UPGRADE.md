# Upgrade Guide

## From 2.x to 3.0

### Requirements

- PHP 8.3 or higher (was 8.1)
- Laravel 11, 12 or 13 (Laravel 10 is no longer supported)

### Dashboard authorization

Outside the `local` environment the dashboard now requires the `viewRouteScope` gate. If you enable RouteScope on staging or elsewhere, define the gate:

```php
Gate::define('viewRouteScope', fn ($user = null) => $user?->isAdmin());
```

Without it, the dashboard returns 403.

### `getAllRoutes()` is deprecated

`getAllRoutes()` still returns the v2 format, so existing code keeps working. Move to the typed API before v4:

```php
// Before
$routes = RouteScope::getAllRoutes();
foreach ($routes['apiRoutes'] as $route) {
    echo $route['method'].' '.$route['path'];
}

// After
foreach (RouteScope::api() as $route) {
    echo implode('|', $route->methods).' '.$route->uri;
}
```

Differences to watch for:

- One `RouteData` per route, with all of its methods in `methods`, instead of one array per method.
- `path` is now `uri`.
- Properties instead of array keys. Use `toArray()` if you need an array (keys are snake_case).
- Filtering by middleware: `$route->hasMiddleware('auth')` instead of `in_array('auth', $route['middleware'])`.

### Excluded patterns

Patterns now match whole path segments instead of any substring: `telescope` hides `/telescope` and `/telescope/requests`, but no longer `/telescopes` or `/shop/telescope`. Use `*` wildcards if you relied on substring matching (e.g. `*telescope*`).

### Published views

If you published the dashboard view, re-publish it: the data now uses `uri` and `methods` instead of `path` and `method`.

```bash
php artisan vendor:publish --tag=routescope-views --force
```
