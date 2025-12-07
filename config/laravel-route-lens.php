<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel Route Lens Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file is used to control the behavior of the
    | Laravel Route Lens package.
    |
    */

    'enabled' => env('LARAVEL_ROUTE_LENS_ENABLED', true),

    'prefix' => env('LARAVEL_ROUTE_LENS_PREFIX', 'route-lens'),

    'excluded_patterns' => [
        'route-lens',
        '_ignition',
        'sanctum/csrf-cookie',
        'telescope',
        '__execute-laravel-error-solution',
        b,
    ],
];
