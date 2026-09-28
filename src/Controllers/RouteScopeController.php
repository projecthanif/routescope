<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Projecthanif\RouteScope\Audit\Auditor;
use Projecthanif\RouteScope\Audit\Issue;
use Projecthanif\RouteScope\Data\RouteData;
use Projecthanif\RouteScope\Services\RouteScopeService;

final class RouteScopeController extends Controller
{
    public function index(RouteScopeService $routeScope, Auditor $auditor): View
    {
        $issues = $auditor->audit()->groupBy(fn (Issue $issue): string => $issue->route->key());

        $toArray = fn (RouteData $route): array => [
            ...$route->toArray(),
            'issues' => $issues->get($route->key(), collect())
                ->map(fn (Issue $issue): array => [
                    'rule' => $issue->rule,
                    'severity' => $issue->severity->value,
                    'message' => $issue->message,
                ])
                ->values()
                ->all(),
        ];

        return view('routescope::routescope', [
            'apiRoutes' => $routeScope->api()->map($toArray)->all(),
            'webRoutes' => $routeScope->web()->map($toArray)->all(),
        ]);
    }
}
