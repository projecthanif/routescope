<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Facades;

use Illuminate\Support\Facades\Facade;
use Projecthanif\RouteScope\Services\RouteScopeService;

/**
 * @method static array{apiRoutes: \Illuminate\Support\Collection<int, array{method: string, path: string, source: string, name: string|null, middleware: list<string>}>, webRoutes: \Illuminate\Support\Collection<int, array{method: string, path: string, source: string, name: string|null, middleware: list<string>}>} getAllRoutes()
 *
 * @see RouteScopeService
 */
final class RouteScope extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RouteScopeService::class;
    }
}
