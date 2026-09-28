<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Audit\Rules;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Projecthanif\RouteScope\Audit\Issue;
use Projecthanif\RouteScope\Audit\Rule;
use Projecthanif\RouteScope\Audit\Severity;
use Projecthanif\RouteScope\Data\RouteData;
use Projecthanif\RouteScope\Services\RouteScopeService;

/**
 * Laravel tries routes in order and uses the first match, so `/users/{user}` registered before
 * `/users/create` catches every request meant for `/users/create`.
 *
 * Detection builds a sample request for each route (parameters filled with "1") and checks it
 * against the routes Laravel would try first, so a shadowing route with a narrower `where()`
 * constraint than the sample may go unnoticed.
 */
final readonly class ShadowedRoute implements Rule
{
    private const array SAMPLES = ['1', 'a'];

    public function __construct(
        private Router $router,
        private RouteScopeService $routeScope,
    ) {}

    public function id(): string
    {
        return 'shadowed-route';
    }

    public function check(): iterable
    {
        $collection = $this->router->getRoutes();
        $reported = [];

        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            /** @var list<Route> $candidates Routes in the order Laravel tries them for this method */
            $candidates = array_values(array_filter(
                $collection->get($method),
                fn (Route $route): bool => ! $route->isFallback,
            ));

            foreach ($candidates as $index => $route) {
                $request = $this->sampleRequest($route, $method);

                if (! $request instanceof Request) {
                    continue;
                }

                foreach (array_slice($candidates, 0, $index) as $earlier) {
                    if (! $earlier->matches($request)) {
                        continue;
                    }

                    $data = $this->routeScope->describe($route);
                    $shadowedBy = $this->routeScope->describe($earlier);

                    if ($data instanceof RouteData && $shadowedBy instanceof RouteData && ! isset($reported[$data->key()])) {
                        $reported[$data->key()] = true;

                        yield new Issue(
                            $this->id(),
                            Severity::Warning,
                            "{$method} {$data->uri} is caught by {$shadowedBy->uri}, which Laravel checks first.",
                            $data,
                            [$shadowedBy],
                        );
                    }

                    break;
                }
            }
        }
    }

    /**
     * A request that this route would match, or null if no sample value satisfies its constraints.
     */
    private function sampleRequest(Route $route, string $method): ?Request
    {
        $path = $this->fill((string) preg_replace('/\/?\{\w+\?\}/', '', $route->uri()), $route->wheres);
        $host = $route->getDomain() === null ? 'localhost' : $this->fill($route->getDomain(), $route->wheres);

        if ($path === null || $host === null) {
            return null;
        }

        return Request::create('http://'.$host.'/'.ltrim($path, '/'), $method);
    }

    /**
     * Replace {parameters} with a sample value that satisfies each parameter's where() pattern.
     *
     * @param  array<array-key, mixed>  $wheres
     */
    private function fill(string $pattern, array $wheres): ?string
    {
        $failed = false;

        $filled = preg_replace_callback('/\{(\w+)\}/', function (array $match) use ($wheres, &$failed): string {
            $constraint = $wheres[$match[1]] ?? null;

            foreach (self::SAMPLES as $sample) {
                if (! is_string($constraint) || preg_match('#^(?:'.$constraint.')$#', $sample) === 1) {
                    return $sample;
                }
            }

            $failed = true;

            return '';
        }, $pattern);

        return $failed || $filled === null ? null : $filled;
    }
}
