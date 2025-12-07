<?php

declare(strict_types=1);

namespace Projecthanif\LaravelRouteLens\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

final class LaravelRouteLensService
{
    private readonly Collection $excludedPatterns;

    public function __construct()
    {
        $this->excludedPatterns = collect(config('laravel-route-lens.excluded_patterns', []));
    }

    /**
     * Get all routes organized by category.
     *
     * @return array<string, mixed>
     */
    public function getAllRoutes(): array
    {
        $routes = $this->getFormattedRoutes();

        return [
            'apiRoutes' => $this->filterApiRoutes($routes),
            'webRoutes' => $this->filterWebRoutes($routes),
        ];
    }

    /**
     * Filter routes that start with /api/.
     *
     * @param  array<int, array<string, mixed>>  $routes
     * @return Collection<int, array<string, mixed>>
     */
    private function filterApiRoutes(array $routes): Collection
    {
        return collect($routes)
            ->filter(fn (array $route): bool => str_starts_with((string) $route['path'], '/api/'))
            ->values();
    }

    /**
     * Filter routes that don't start with /api/.
     *
     * @param  array<int, array<string, mixed>>  $routes
     * @return Collection<int, array<string, mixed>>
     */
    private function filterWebRoutes(array $routes): Collection
    {
        return collect($routes)
            ->filter(fn (array $route): bool => ! str_starts_with((string) $route['path'], '/api/'))
            ->values();
    }

    /**
     * @return array{method: mixed, path: non-falsy-string, source: mixed, name: mixed, middleware: mixed}[]
     */
    private function getFormattedRoutes(): array
    {
        $routes = [];
        $routeCollection = Route::getRoutes();

        foreach ($routeCollection as $route) {
            // Skip routes without names or that are internal (like routelens itself)
            $uri = $route->uri();

            // Skip certain internal routes if needed
            if ($this->shouldSkipRoute($uri)) {
                continue;
            }

            $methods = $route->methods();
            // Filter out HEAD and OPTIONS methods for cleaner display
            $methods = array_filter($methods, fn($method): bool => ! in_array($method, ['HEAD', 'OPTIONS']));

            if ($methods === []) {
                continue;
            }

            foreach ($methods as $method) {
                $routes[] = [
                    'method' => $method,
                    'path' => '/'.$uri,
                    'source' => $this->getRouteSource($route),
                    'name' => $route->getName(),
                    'middleware' => $route->middleware(),
                ];
            }
        }

        // Sort routes by path
        usort($routes, fn(array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));

        return $routes;
    }

    /**
     * Determine if a route should be skipped.
     */
    private function shouldSkipRoute($uri): bool
    {
        return $this->excludedPatterns->some(
            fn (string $pattern): bool => str_contains((string) $uri, $pattern),
        );
    }

    /**
     * Get the source (controller/action) for a route
     */
    private function getRouteSource($route): string
    {
        $action = $route->getAction();

        if (isset($action['controller'])) {
            // Format: Controller@method or Controller::class
            $controller = $action['controller'];

            if (is_string($controller)) {
                // Handle both "Controller@method" and "Controller::method" formats
                $parts = preg_split('/[@]|::/', $controller);

                if (count($parts) === 2) {
                    // Get the short class name
                    $className = class_basename($parts[0]);
                    $method = $parts[1];

                    // Get a shortened namespace path
                    $namespace = $this->getShortenedNamespace($parts[0]);

                    return "{$namespace}/{$className}::{$method}";
                }

                // Just return the class name if no method
                return class_basename($controller);
            }
        }

        // Check if it's a closure
        if (isset($action['uses']) && $action['uses'] instanceof \Closure) {
            return 'Closure';
        }

        // Fallback
        return 'routes/web.php';
    }

    /**
     * Get a shortened namespace path
     */
    private function getShortenedNamespace(string $fullClass): string
    {
        // Remove App\ prefix
        $path = str_replace('App\\', '', $fullClass);

        // Split by backslashes
        $parts = explode('\\', $path);

        // Remove the class name (last part)
        array_pop($parts);

        if ($parts === []) {
            return 'app';
        }

        // Convert to path format and shorten
        $path = strtolower(implode('/', $parts));

        // Shorten long paths with ellipsis
        if (strlen($path) > 30) {
            $pathParts = explode('/', $path);
            if (count($pathParts) > 3) {
                return $pathParts[0].'/.../'.end($pathParts);
            }
        }

        return $path;
    }
}
