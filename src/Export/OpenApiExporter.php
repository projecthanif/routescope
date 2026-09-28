<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Export;

use Illuminate\Support\Collection;
use Projecthanif\RouteScope\Data\RouteData;
use Projecthanif\RouteScope\Data\RouteParameter;

/**
 * An OpenAPI 3.1 skeleton: paths, operations and path parameters. Request bodies, responses
 * and security can't be derived from routes, so every operation gets a placeholder response
 * and its middleware under `x-middleware` to fill the rest in from.
 */
final readonly class OpenApiExporter implements Exporter
{
    /**
     * HTTP methods OpenAPI supports as operations.
     */
    private const array METHODS = ['GET', 'PUT', 'POST', 'DELETE', 'PATCH'];

    public function __construct(
        private string $title = 'API',
        private string $version = '1.0.0',
    ) {}

    public function export(Collection $routes): string
    {
        $paths = [];
        $operationIds = [];

        foreach ($routes as $route) {
            if ($route->isFallback) {
                continue;
            }

            foreach ($this->pathVariants($route) as [$path, $parameters]) {
                foreach ($route->methods as $method) {
                    if (! in_array($method, self::METHODS, true)) {
                        continue;
                    }

                    $paths[$path][strtolower($method)] = $this->operation($route, $method, $path, $parameters, $operationIds);
                }
            }
        }

        ksort($paths);

        $document = [
            'openapi' => '3.1.0',
            'info' => ['title' => $this->title, 'version' => $this->version],
            'paths' => $paths === [] ? new \stdClass : $paths,
        ];

        return json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * OpenAPI has no optional path parameters, so "/teams/{team}/{tab?}" becomes both
     * "/teams/{team}" and "/teams/{team}/{tab}".
     *
     * @return list<array{string, list<RouteParameter>}>
     */
    private function pathVariants(RouteData $route): array
    {
        $segments = explode('/', ltrim($route->uri, '/'));
        $required = [];
        $variants = [];
        $parameters = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\{(\w+)\?\}$/', $segment, $match) === 1) {
                $variants[] = ['/'.implode('/', $required), $parameters];
                $segment = '{'.$match[1].'}';
            }

            $required[] = $segment;

            if (preg_match('/^\{(\w+)\}$/', $segment, $match) === 1) {
                $parameters[] = $this->parameter($route, $match[1]);
            }
        }

        $variants[] = ['/'.implode('/', $required), $parameters];

        return $variants;
    }

    private function parameter(RouteData $route, string $name): RouteParameter
    {
        foreach ($route->parameters as $parameter) {
            if ($parameter->name === $name) {
                return $parameter;
            }
        }

        return new RouteParameter($name, false, null);
    }

    /**
     * @param  list<RouteParameter>  $parameters
     * @param  array<string, true>  $operationIds  Used operation ids, to keep them unique
     * @return array<string, mixed>
     */
    private function operation(RouteData $route, string $method, string $path, array $parameters, array &$operationIds): array
    {
        $operation = [
            'operationId' => $this->operationId($route, $method, $path, $operationIds),
            'summary' => $route->name ?? $route->source,
            'tags' => [$this->tag($path)],
        ];

        if ($parameters !== []) {
            $operation['parameters'] = array_map(fn (RouteParameter $parameter): array => [
                'name' => $parameter->name,
                'in' => 'path',
                'required' => true,
                'schema' => $this->schema($parameter),
            ], $parameters);
        }

        $operation['responses'] = ['200' => ['description' => 'Successful response']];

        if ($route->middleware !== []) {
            $operation['x-middleware'] = $route->middleware;
        }

        if ($route->domain !== null) {
            $operation['x-domain'] = $route->domain;
        }

        return $operation;
    }

    /**
     * @return array<string, string>
     */
    private function schema(RouteParameter $parameter): array
    {
        return match ($parameter->pattern) {
            null => ['type' => 'string'],
            '[0-9]+', '\d+' => ['type' => 'integer'],
            default => ['type' => 'string', 'pattern' => '^(?:'.$parameter->pattern.')$'],
        };
    }

    /**
     * The route name if it has one, otherwise the method and path, e.g. "getApiUsersUser".
     *
     * @param  array<string, true>  $used
     */
    private function operationId(RouteData $route, string $method, string $path, array &$used): string
    {
        $base = $route->name !== null && count($route->methods) === 1 && ! str_contains($route->uri, '?}')
            ? $route->name
            : strtolower($method).str_replace(' ', '', ucwords((string) preg_replace('/[^A-Za-z0-9]+/', ' ', $path)));

        $id = $base;

        for ($suffix = 2; isset($used[$id]); $suffix++) {
            $id = $base.$suffix;
        }

        $used[$id] = true;

        return $id;
    }

    /**
     * Group operations by their first path segment after "api" and version segments,
     * e.g. "/api/v1/users/{user}" is tagged "users".
     */
    private function tag(string $path): string
    {
        foreach (explode('/', trim($path, '/')) as $segment) {
            if ($segment !== '' && preg_match('/^(api|v\d+|\{.*\})$/i', $segment) !== 1) {
                return $segment;
            }
        }

        return 'default';
    }
}
