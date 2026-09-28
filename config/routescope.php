<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RouteScope Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file is used to control the behavior of the
    | RouteScope package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | When null (the default), RouteScope is enabled in the "local" and
    | "development" environments only. Set ROUTESCOPE_ENABLED to force it.
    |
    | Don't call app() here: published config files are loaded before the
    | application environment is known.
    |
    */

    'enabled' => env('ROUTESCOPE_ENABLED'),

    'prefix' => env('ROUTESCOPE_PREFIX', 'routescope'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to the dashboard. Outside the local environment,
    | access also requires the "viewRouteScope" gate to pass, e.g.:
    |
    |   Gate::define('viewRouteScope', fn ($user) => $user->isAdmin());
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Excluded Patterns
    |--------------------------------------------------------------------------
    |
    | Routes whose URI matches one of these patterns are hidden. A pattern
    | matches the path itself and everything beneath it, and supports `*`
    | wildcards (e.g. "admin/*-debug"). RouteScope's own routes are always hidden.
    |
    */

    'excluded_patterns' => [
        '_ignition',
        'sanctum/csrf-cookie',
        'telescope',
        '_debugbar',
        '_boost',
        '__execute-laravel-error-solution',
    ],
];
