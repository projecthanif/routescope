<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Tests\Fixtures;

use Projecthanif\RouteScope\Audit\Issue;
use Projecthanif\RouteScope\Audit\Rule;
use Projecthanif\RouteScope\Audit\Severity;
use Projecthanif\RouteScope\Services\RouteScopeService;

final readonly class AlwaysFailsRule implements Rule
{
    public function __construct(private RouteScopeService $routeScope) {}

    public function id(): string
    {
        return 'always-fails';
    }

    public function check(): iterable
    {
        foreach ($this->routeScope->all() as $route) {
            yield new Issue($this->id(), Severity::Error, 'Custom rule.', $route);
        }
    }
}
