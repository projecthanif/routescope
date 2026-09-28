<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Services;

use Illuminate\Routing\RedirectController;
use Illuminate\Routing\Route;
use Illuminate\Routing\ViewController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * @phpstan-type RouteArray array{method: string, path: string, source: string, name: string|null, middleware: list<string>}
 */
final class RouteScopeService
{
    /**
     * Display order for HTTP methods sharing the same path.
     */
    private const METHOD_ORDER = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Get all routes organized by category.
     *
     * @return array{apiRoutes: Collection<int, RouteArray>, webRoutes: Collection<int, RouteArray>}
     */
    public function getAllRoutes(): array
    {
        $routes = collect($this->getFormattedRoutes());

        return [
            'apiRoutes' => $routes->filter(fn (array $route): bool => $route['is_api'])->map($this->withoutApiFlag(...))->values(),
            'webRoutes' => $routes->reject(fn (array $route): bool => $route['is_api'])->map($this->withoutApiFlag(...))->values(),
        ];
    }

    /**
     * @param  array{method: string, path: string, source: string, name: string|null, middleware: list<string>, is_api: bool}  $route
     * @return RouteArray
     */
    private function withoutApiFlag(array $route): array
    {
        return [
            'method' => $route['method'],
            'path' => $route['path'],
            'source' => $route['source'],
            'name' => $route['name'],
            'middleware' => $route['middleware'],
        ];
    }

    /**
     * @return list<array{method: string, path: string, source: string, name: string|null, middleware: list<string>, is_api: bool}>
     */
    private function getFormattedRoutes(): array
    {
        $routes = [];

        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            if ($this->shouldSkipRoute($route)) {
                continue;
            }

            // Filter out HEAD and OPTIONS methods for cleaner display
            $methods = array_filter(
                $route->methods(),
                fn (mixed $method): bool => is_string($method) && ! in_array($method, ['HEAD', 'OPTIONS'], true),
            );

            $middleware = $this->getMiddleware($route);
            $source = $this->getRouteSource($route);
            $isApi = $this->isApiRoute($route, $middleware);

            foreach ($methods as $method) {
                $routes[] = [
                    'method' => $method,
                    'path' => '/'.ltrim($route->uri(), '/'),
                    'source' => $source,
                    'name' => $route->getName(),
                    'middleware' => $middleware,
                    'is_api' => $isApi,
                ];
            }
        }

        usort($routes, fn (array $a, array $b): int => [$a['path'], $this->methodRank($a['method'])]
            <=> [$b['path'], $this->methodRank($b['method'])]);

        return $routes;
    }

    /**
     * Determine if a route should be skipped.
     *
     * Patterns support `*` wildcards and match the given path and everything beneath it,
     * so "telescope" hides "telescope" and "telescope/requests" but not "telescopes".
     */
    private function shouldSkipRoute(Route $route): bool
    {
        if (str_starts_with((string) $route->getName(), 'routescope.')) {
            return true;
        }

        $uri = trim($route->uri(), '/');

        return collect($this->excludedPatterns())->contains(
            fn (string $pattern): bool => Str::is([$pattern, $pattern.'/*'], $uri),
        );
    }

    /**
     * @return list<string>
     */
    private function excludedPatterns(): array
    {
        $patterns = config('routescope.excluded_patterns', []);

        if (! is_array($patterns)) {
            return [];
        }

        return array_values(array_map(
            fn (mixed $pattern): string => trim(is_scalar($pattern) ? (string) $pattern : '', '/'),
            $patterns,
        ));
    }

    /**
     * @return list<string>
     */
    private function getMiddleware(Route $route): array
    {
        return array_values(array_filter($route->middleware(), is_string(...)));
    }

    /**
     * A route is an API route when it uses the "api" middleware group or lives under the /api prefix.
     *
     * @param  list<string>  $middleware
     */
    private function isApiRoute(Route $route, array $middleware): bool
    {
        $uri = trim($route->uri(), '/');

        return in_array('api', $middleware, true) || $uri === 'api' || str_starts_with($uri, 'api/');
    }

    private function methodRank(string $method): int
    {
        $rank = array_search($method, self::METHOD_ORDER, true);

        return $rank === false ? count(self::METHOD_ORDER) : $rank;
    }

    /**
     * Get the source (controller/action) for a route.
     */
    private function getRouteSource(Route $route): string
    {
        // Returns "Closure" for closures, including serialized closures from `route:cache`.
        $action = $route->getActionName();

        if ($action === 'Closure') {
            return 'Closure';
        }

        [$class, $method] = array_pad(explode('@', ltrim($action, '\\'), 2), 2, '__invoke');

        if ($class === ViewController::class) {
            $view = $route->defaults['view'] ?? null;

            return 'View: '.(is_string($view) ? $view : 'unknown');
        }

        if ($class === RedirectController::class) {
            $destination = $route->defaults['destination'] ?? null;

            return 'Redirect: '.(is_string($destination) ? $destination : 'unknown');
        }

        return $this->getShortenedNamespace($class).'/'.class_basename($class).'::'.$method;
    }

    /**
     * Get a shortened namespace path.
     */
    private function getShortenedNamespace(string $fullClass): string
    {
        $parts = explode('\\', str_starts_with($fullClass, 'App\\') ? substr($fullClass, 4) : $fullClass);

        // Remove the class name (last part)
        array_pop($parts);

        if ($parts === []) {
            return 'app';
        }

        $path = strtolower(implode('/', $parts));

        // Shorten long paths with ellipsis
        if (strlen($path) > 30 && count($parts) > 3) {
            return strtolower($parts[0]).'/.../'.strtolower(end($parts));
        }

        return $path;
    }
}
