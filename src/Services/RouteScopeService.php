<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Services;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\RedirectController;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\SortedMiddleware;
use Illuminate\Routing\ViewController;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Projecthanif\RouteScope\Data\RouteData;
use Projecthanif\RouteScope\Data\RouteParameter;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * @phpstan-type LegacyRouteArray array{method: string, path: string, source: string, name: string|null, middleware: list<string>}
 */
final readonly class RouteScopeService
{
    /**
     * Display order for HTTP methods.
     */
    private const array METHOD_ORDER = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private Router $router,
        private Container $container,
    ) {}

    /**
     * Get every route, sorted by URI and then by HTTP method.
     *
     * @return Collection<int, RouteData>
     */
    public function all(): Collection
    {
        $routes = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            $data = $this->describe($route);

            if ($data instanceof RouteData) {
                $routes[] = $data;
            }
        }

        usort($routes, fn (RouteData $a, RouteData $b): int => [$a->uri, $this->methodRank($a->methods[0])]
            <=> [$b->uri, $this->methodRank($b->methods[0])]);

        return collect($routes);
    }

    /**
     * Describe a single route, or return null if it is excluded or only answers HEAD/OPTIONS.
     */
    public function describe(Route $route): ?RouteData
    {
        if ($this->shouldSkipRoute($route)) {
            return null;
        }

        $methods = $this->getMethods($route);

        return $methods === [] ? null : $this->toRouteData($route, $methods);
    }

    /**
     * HTTP methods, without HEAD and OPTIONS, in display order.
     *
     * @return list<string>
     */
    public function getMethods(Route $route): array
    {
        $methods = array_values(array_filter(
            $route->methods(),
            fn (mixed $method): bool => is_string($method) && ! in_array($method, ['HEAD', 'OPTIONS'], true),
        ));

        usort($methods, fn (string $a, string $b): int => $this->methodRank($a) <=> $this->methodRank($b));

        return $methods;
    }

    /**
     * Global middleware from the HTTP kernel, which runs before any route middleware.
     *
     * @return list<string>
     */
    public function globalMiddleware(): array
    {
        $kernel = $this->httpKernel();

        return $kernel instanceof HttpKernel
            ? array_values(array_filter($kernel->getGlobalMiddleware(), is_string(...)))
            : [];
    }

    /**
     * Routes under /api or using the "api" middleware group.
     *
     * @return Collection<int, RouteData>
     */
    public function api(): Collection
    {
        return $this->filter(fn (RouteData $route): bool => $route->isApi);
    }

    /**
     * All routes that are not API routes.
     *
     * @return Collection<int, RouteData>
     */
    public function web(): Collection
    {
        return $this->filter(fn (RouteData $route): bool => ! $route->isApi);
    }

    /**
     * @param  callable(RouteData): bool  $callback
     * @return Collection<int, RouteData>
     */
    public function filter(callable $callback): Collection
    {
        return $this->all()->filter($callback)->values();
    }

    /**
     * Get all routes in the v2 format, with one entry per HTTP method.
     *
     * @deprecated Use all(), api() or web() instead. Will be removed in v4.
     *
     * @return array{apiRoutes: Collection<int, LegacyRouteArray>, webRoutes: Collection<int, LegacyRouteArray>}
     */
    public function getAllRoutes(): array
    {
        return [
            'apiRoutes' => $this->toLegacyFormat($this->api()),
            'webRoutes' => $this->toLegacyFormat($this->web()),
        ];
    }

    /**
     * @param  Collection<int, RouteData>  $routes
     * @return Collection<int, LegacyRouteArray>
     */
    private function toLegacyFormat(Collection $routes): Collection
    {
        return $routes->flatMap(fn (RouteData $route): array => array_map(fn (string $method): array => [
            'method' => $method,
            'path' => $route->uri,
            'source' => $route->source,
            'name' => $route->name,
            'middleware' => $route->middleware,
        ], $route->methods))->values();
    }

    /**
     * @param  list<string>  $methods
     */
    private function toRouteData(Route $route, array $methods): RouteData
    {
        $middleware = $this->getMiddleware($route);
        $reflection = $this->reflectAction($route);

        return new RouteData(
            methods: $methods,
            uri: '/'.ltrim($route->uri(), '/'),
            name: $route->getName(),
            domain: $route->getDomain(),
            action: ltrim($route->getActionName(), '\\'),
            source: $this->getRouteSource($route),
            middleware: $middleware,
            resolvedMiddleware: $this->getResolvedMiddleware($route),
            parameters: $this->getParameters($route),
            file: $this->relativePath($reflection?->getFileName()),
            line: $reflection?->getStartLine() ?: null,
            isApi: $this->isApiRoute($route, $middleware),
            isFallback: $route->isFallback,
        );
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
     * Route and controller middleware with groups and aliases expanded, sorted by middleware
     * priority: the order Laravel runs them in.
     *
     * @return list<string>
     */
    private function getResolvedMiddleware(Route $route): array
    {
        $sorted = (new SortedMiddleware($this->middlewarePriority(), $this->router->gatherRouteMiddleware($route)))->all();

        return array_values(array_filter($sorted, is_string(...)));
    }

    /**
     * @return array<array-key, mixed>
     */
    private function middlewarePriority(): array
    {
        $kernel = $this->httpKernel();

        return $kernel instanceof HttpKernel ? $kernel->getMiddlewarePriority() : $this->router->middlewarePriority;
    }

    /**
     * The HTTP kernel, if the app has one. Resolving it also syncs its middleware priority to the router.
     */
    private function httpKernel(): ?object
    {
        return $this->container->bound(HttpKernelContract::class) ? $this->container->make(HttpKernelContract::class) : null;
    }

    /**
     * @return list<RouteParameter>
     */
    private function getParameters(Route $route): array
    {
        preg_match_all('/\{(\w+)(\?)?\}/', $route->uri(), $matches, PREG_SET_ORDER);

        return array_map(function (array $match) use ($route): RouteParameter {
            $pattern = $route->wheres[$match[1]] ?? null;

            return new RouteParameter(
                name: $match[1],
                optional: isset($match[2]),
                pattern: is_string($pattern) ? $pattern : null,
            );
        }, $matches);
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
     * Reflect the controller method or closure behind a route, if it can be loaded.
     */
    private function reflectAction(Route $route): ?ReflectionFunctionAbstract
    {
        $uses = $route->getAction('uses');

        if ($uses instanceof Closure) {
            return new ReflectionFunction($uses);
        }

        if ($route->getActionName() === 'Closure') {
            return null;
        }

        [$class, $method] = $this->splitAction($route);

        // The framework's own view/redirect controllers aren't useful to point at.
        if (in_array($class, [ViewController::class, RedirectController::class], true)) {
            return null;
        }

        try {
            return new ReflectionMethod($class, $method);
        } catch (ReflectionException) {
            return null;
        }
    }

    private function relativePath(string|false|null $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $base = rtrim(base_path(), '/').'/';

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }

    /**
     * Get the source (controller/action) for a route.
     */
    private function getRouteSource(Route $route): string
    {
        // Returns "Closure" for closures, including serialized closures from `route:cache`.
        if ($route->getActionName() === 'Closure') {
            return 'Closure';
        }

        [$class, $method] = $this->splitAction($route);

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
     * Split a controller action into its class and method, defaulting to __invoke.
     *
     * @return array{string, string}
     */
    private function splitAction(Route $route): array
    {
        [$class, $method] = array_pad(explode('@', ltrim($route->getActionName(), '\\'), 2), 2, '__invoke');

        return [$class, $method];
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
