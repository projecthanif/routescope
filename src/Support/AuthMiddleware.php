<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Support;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Routing\Middleware\ValidateSignature;
use Projecthanif\RouteScope\Data\RouteData;

/**
 * Decides whether a route requires authentication, using `routescope.audit.auth_middleware`.
 * Shared by the api-without-auth audit rule and the dashboard's "No auth" filter.
 */
final readonly class AuthMiddleware
{
    public const array DEFAULT = ['auth', 'auth.basic', 'signed', Authenticate::class, AuthenticateWithBasicAuth::class, ValidateSignature::class];

    /**
     * @var list<string>
     */
    private array $middleware;

    public function __construct()
    {
        $middleware = config('routescope.audit.auth_middleware', self::DEFAULT);

        $this->middleware = is_array($middleware) ? array_values(array_filter($middleware, is_string(...))) : self::DEFAULT;
    }

    public function protects(RouteData $route): bool
    {
        foreach ($this->middleware as $candidate) {
            if ($route->hasMiddleware($candidate)) {
                return true;
            }
        }

        return false;
    }
}
