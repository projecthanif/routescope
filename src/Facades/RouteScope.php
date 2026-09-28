<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Facades;

use Illuminate\Support\Facades\Facade;
use Projecthanif\RouteScope\Services\RouteScopeService;

/**
 * @method static \Illuminate\Support\Collection<int, \Projecthanif\RouteScope\Data\RouteData> all()
 * @method static \Illuminate\Support\Collection<int, \Projecthanif\RouteScope\Data\RouteData> api()
 * @method static \Illuminate\Support\Collection<int, \Projecthanif\RouteScope\Data\RouteData> web()
 * @method static list<string> globalMiddleware()
 * @method static \Illuminate\Support\Collection<int, \Projecthanif\RouteScope\Data\RouteData> filter(callable(\Projecthanif\RouteScope\Data\RouteData): bool $callback)
 * @method static array{apiRoutes: \Illuminate\Support\Collection<int, array{method: string, path: string, source: string, name: string|null, middleware: list<string>}>, webRoutes: \Illuminate\Support\Collection<int, array{method: string, path: string, source: string, name: string|null, middleware: list<string>}>} getAllRoutes() Deprecated: use all(), api() or web().
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
