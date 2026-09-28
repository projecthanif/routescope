<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Audit\Rules;

use Projecthanif\RouteScope\Audit\Issue;
use Projecthanif\RouteScope\Audit\Rule;
use Projecthanif\RouteScope\Audit\Severity;
use Projecthanif\RouteScope\Services\RouteScopeService;
use Projecthanif\RouteScope\Support\AuthMiddleware;

/**
 * API routes without any authentication middleware. Signed URLs count as protected. Intentionally
 * public endpoints (login, webhooks, ...) can be listed under `audit.ignore.api-without-auth`.
 */
final readonly class ApiWithoutAuth implements Rule
{
    public function __construct(private RouteScopeService $routeScope) {}

    public function id(): string
    {
        return 'api-without-auth';
    }

    public function check(): iterable
    {
        $auth = new AuthMiddleware;

        foreach ($this->routeScope->api() as $route) {
            if (! $auth->protects($route)) {
                yield new Issue(
                    $this->id(),
                    Severity::Warning,
                    'API route has no authentication middleware.',
                    $route,
                );
            }
        }
    }
}
