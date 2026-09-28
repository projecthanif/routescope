<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Audit\Rules;

use Illuminate\Routing\RedirectController;
use Illuminate\Routing\ViewController;
use Projecthanif\RouteScope\Audit\Issue;
use Projecthanif\RouteScope\Audit\Rule;
use Projecthanif\RouteScope\Audit\Severity;
use Projecthanif\RouteScope\Services\RouteScopeService;

/**
 * Routes pointing at a controller class or method that doesn't exist fail on every request.
 */
final readonly class MissingAction implements Rule
{
    public function __construct(private RouteScopeService $routeScope) {}

    public function id(): string
    {
        return 'missing-action';
    }

    public function check(): iterable
    {
        foreach ($this->routeScope->all() as $route) {
            if ($route->action === 'Closure') {
                continue;
            }

            [$class, $method] = array_pad(explode('@', $route->action, 2), 2, '__invoke');

            if (in_array($class, [ViewController::class, RedirectController::class], true)) {
                continue;
            }

            if (! class_exists($class)) {
                yield new Issue($this->id(), Severity::Error, "Controller [{$class}] does not exist.", $route);
            } elseif (! method_exists($class, $method)) {
                yield new Issue($this->id(), Severity::Error, "Method [{$class}::{$method}] does not exist.", $route);
            }
        }
    }
}
