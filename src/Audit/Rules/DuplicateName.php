<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Audit\Rules;

use Illuminate\Support\Collection;
use Projecthanif\RouteScope\Audit\Issue;
use Projecthanif\RouteScope\Audit\Rule;
use Projecthanif\RouteScope\Audit\Severity;
use Projecthanif\RouteScope\Data\RouteData;
use Projecthanif\RouteScope\Services\RouteScopeService;

/**
 * When several routes share a name, route() only ever generates a URL for the last one registered.
 */
final readonly class DuplicateName implements Rule
{
    public function __construct(private RouteScopeService $routeScope) {}

    public function id(): string
    {
        return 'duplicate-name';
    }

    public function check(): iterable
    {
        $groups = $this->routeScope->all()
            ->filter(fn (RouteData $route): bool => $route->name !== null)
            ->groupBy(fn (RouteData $route): string => (string) $route->name)
            ->filter(fn (Collection $routes): bool => $routes->count() > 1);

        foreach ($groups as $name => $routes) {
            foreach ($routes as $route) {
                $others = array_values($routes->reject(fn (RouteData $other): bool => $other === $route)->all());
                $uris = implode(', ', array_map(fn (RouteData $other): string => $other->uri, $others));

                yield new Issue(
                    $this->id(),
                    Severity::Warning,
                    "Route name [{$name}] is also used by {$uris}.",
                    $route,
                    $others,
                );
            }
        }
    }
}
