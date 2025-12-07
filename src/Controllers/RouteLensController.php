<?php

declare(strict_types=1);

namespace Projecthanif\LaravelRouteLens\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Projecthanif\LaravelRouteLens\Facades\RouteLens;

final class RouteLensController extends Controller
{
    public function index(Request $request)
    {
        $routes = RouteLens::getAllRoutes();

        $apiRoutes = $routes['apiRoutes']->toArray();
        $webRoutes = $routes['webRoutes']->toArray();

        return view('laravel-route-lens::routelens', ['apiRoutes' => $apiRoutes, 'webRoutes' => $webRoutes]);
    }
}
