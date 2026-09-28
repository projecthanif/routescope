<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Audit\Rules;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Projecthanif\RouteScope\Audit\Issue;
use Projecthanif\RouteScope\Audit\Rule;
use Projecthanif\RouteScope\Audit\Severity;
use Projecthanif\RouteScope\Data\RouteData;
use Projecthanif\RouteScope\Services\RouteScopeService;

/**
 * Laravel keeps one route per method and URI. Registering the same method and URI again silently
 * replaces the earlier definition for that method, e.g. `Route::get('/x')` followed by
 * `Route::match(['get', 'post'], '/x')`, so the earlier one never runs.
 */
final readonly class OverriddenRoute implements Rule
{
    public function __construct(
        private Router $router,
        private RouteScopeService $routeScope,
    ) {}

    public function id(): string
    {
        return 'overridden-route';
    }

    public function check(): iterable
    {
        $collection = $this->router->getRoutes();

        foreach ($collection->getRoutes() as $route) {
            $data = $this->routeScope->describe($route);

            if (! $data instanceof RouteData) {
                continue;
            }

            $overriddenBy = [];

            foreach ($data->methods as $method) {
                $winner = $collection->get($method)[$route->getDomain().$route->uri()] ?? null;

                if ($winner instanceof Route && $winner !== $route) {
                    $overriddenBy[$method] = $winner;
                }
            }

            if ($overriddenBy === []) {
                continue;
            }

            $unique = [];

            foreach ($overriddenBy as $winner) {
                $unique[spl_object_id($winner)] = $winner;
            }

            $winners = array_values(array_filter(array_map($this->routeScope->describe(...), array_values($unique))));

            yield new Issue(
                $this->id(),
                Severity::Warning,
                sprintf(
                    '%s %s is replaced by a later definition of the same URI and never runs for %s.',
                    implode('|', $data->methods),
                    $data->uri,
                    implode(', ', array_keys($overriddenBy)),
                ),
                $data,
                $winners,
            );
        }
    }
}
