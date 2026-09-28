<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Projecthanif\RouteScope\Facades\RouteScope;

final class RouteScopeController extends Controller
{
    public function index(): View
    {
        $routes = RouteScope::getAllRoutes();

        return view('routescope::routescope', [
            'apiRoutes' => $routes['apiRoutes']->all(),
            'webRoutes' => $routes['webRoutes']->all(),
        ]);
    }
}
