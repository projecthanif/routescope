<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Export;

use Illuminate\Support\Collection;

/**
 * Every RouteData field, one object per route.
 */
final class JsonExporter implements Exporter
{
    public function export(Collection $routes): string
    {
        return json_encode($routes->values(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    }
}
