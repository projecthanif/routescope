<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Data;

use JsonSerializable;

/**
 * @phpstan-type RouteDataArray array{
 *     methods: list<string>,
 *     uri: string,
 *     name: string|null,
 *     domain: string|null,
 *     action: string,
 *     source: string,
 *     middleware: list<string>,
 *     resolved_middleware: list<string>,
 *     parameters: list<array{name: string, optional: bool, pattern: string|null}>,
 *     file: string|null,
 *     line: int|null,
 *     is_api: bool,
 *     is_fallback: bool,
 * }
 */
final readonly class RouteData implements JsonSerializable
{
    /**
     * @param  list<string>  $methods  HTTP methods, excluding HEAD and OPTIONS
     * @param  string  $uri  URI with a leading slash, e.g. "/users/{user}"
     * @param  string  $action  Raw action name, e.g. "App\Http\Controllers\UserController@show" or "Closure"
     * @param  string  $source  Short human-readable action, e.g. "http/controllers/UserController::show"
     * @param  list<string>  $middleware  Middleware as declared on the route
     * @param  list<string>  $resolvedMiddleware  Middleware after expanding groups and aliases
     * @param  list<RouteParameter>  $parameters
     * @param  string|null  $file  Path of the action's definition, relative to the app base path when inside it
     */
    public function __construct(
        public array $methods,
        public string $uri,
        public ?string $name,
        public ?string $domain,
        public string $action,
        public string $source,
        public array $middleware,
        public array $resolvedMiddleware,
        public array $parameters,
        public ?string $file,
        public ?int $line,
        public bool $isApi,
        public bool $isFallback,
    ) {}

    /**
     * Uniquely identifies the route, e.g. "GET|POST {account}.example.com/users".
     */
    public function key(): string
    {
        return implode('|', $this->methods).' '.$this->domain.$this->uri;
    }

    public function hasMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods, true);
    }

    /**
     * Whether the route uses the given middleware, either as declared or after resolution.
     * Matches parameterized middleware too, so "throttle" matches "throttle:60,1".
     */
    public function hasMiddleware(string $middleware): bool
    {
        foreach ([...$this->middleware, ...$this->resolvedMiddleware] as $candidate) {
            if ($candidate === $middleware || str_starts_with($candidate, $middleware.':')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return RouteDataArray
     */
    public function toArray(): array
    {
        return [
            'methods' => $this->methods,
            'uri' => $this->uri,
            'name' => $this->name,
            'domain' => $this->domain,
            'action' => $this->action,
            'source' => $this->source,
            'middleware' => $this->middleware,
            'resolved_middleware' => $this->resolvedMiddleware,
            'parameters' => array_map(fn (RouteParameter $parameter): array => $parameter->toArray(), $this->parameters),
            'file' => $this->file,
            'line' => $this->line,
            'is_api' => $this->isApi,
            'is_fallback' => $this->isFallback,
        ];
    }

    /**
     * @return RouteDataArray
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
