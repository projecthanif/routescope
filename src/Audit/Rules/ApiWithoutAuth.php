<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Audit\Rules;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Routing\Middleware\ValidateSignature;
use Projecthanif\RouteScope\Audit\Issue;
use Projecthanif\RouteScope\Audit\Rule;
use Projecthanif\RouteScope\Audit\Severity;
use Projecthanif\RouteScope\Data\RouteData;
use Projecthanif\RouteScope\Services\RouteScopeService;

/**
 * API routes without any authentication middleware. Signed URLs count as protected. Intentionally public endpoints (login,
 * webhooks, ...) can be listed under `audit.ignore.api-without-auth`.
 */
final readonly class ApiWithoutAuth implements Rule
{
    public const array DEFAULT_MIDDLEWARE = ['auth', 'auth.basic', 'signed', Authenticate::class, AuthenticateWithBasicAuth::class, ValidateSignature::class];

    public function __construct(private RouteScopeService $routeScope) {}

    public function id(): string
    {
        return 'api-without-auth';
    }

    public function check(): iterable
    {
        $middleware = $this->authMiddleware();

        foreach ($this->routeScope->api() as $route) {
            if (! $this->isProtected($route, $middleware)) {
                yield new Issue(
                    $this->id(),
                    Severity::Warning,
                    'API route has no authentication middleware.',
                    $route,
                );
            }
        }
    }

    /**
     * @param  list<string>  $middleware
     */
    private function isProtected(RouteData $route, array $middleware): bool
    {
        foreach ($middleware as $candidate) {
            if ($route->hasMiddleware($candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function authMiddleware(): array
    {
        $middleware = config('routescope.audit.auth_middleware', self::DEFAULT_MIDDLEWARE);

        return is_array($middleware) ? array_values(array_filter($middleware, is_string(...))) : self::DEFAULT_MIDDLEWARE;
    }
}
