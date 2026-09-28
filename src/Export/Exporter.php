<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Export;

use Illuminate\Support\Collection;
use Projecthanif\RouteScope\Data\RouteData;

interface Exporter
{
    /**
     * @param  Collection<int, RouteData>  $routes
     */
    public function export(Collection $routes): string;
}
