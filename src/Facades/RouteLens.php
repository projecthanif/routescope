<?php

declare(strict_types=1);

namespace Projecthanif\LaravelRouteLens\Facades;

use Illuminate\Support\Facades\Facade;
use Projecthanif\LaravelRouteLens\Services\LaravelRouteLensService;

/**
 * @method static array getAllRoutes()
 *
 * @see LaravelRouteLensService
 */
final class RouteLens extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LaravelRouteLensService::class;
    }
}
