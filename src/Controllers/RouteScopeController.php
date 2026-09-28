<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Projecthanif\RouteScope\Data\RouteData;
use Projecthanif\RouteScope\Services\RouteScopeService;

final class RouteScopeController extends Controller
{
    public function index(RouteScopeService $routeScope): View
    {
        $toArray = fn (RouteData $route): array => $route->toArray();

        return view('routescope::routescope', [
            'apiRoutes' => $routeScope->api()->map($toArray)->all(),
            'webRoutes' => $routeScope->web()->map($toArray)->all(),
        ]);
    }
}
