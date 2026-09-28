<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Audit;

use JsonSerializable;
use Projecthanif\RouteScope\Data\RouteData;

final readonly class Issue implements JsonSerializable
{
    /**
     * @param  string  $rule  Identifier of the rule that raised the issue, e.g. "shadowed-route"
     * @param  RouteData  $route  The route the issue is about
     * @param  list<RouteData>  $related  Other routes involved, e.g. the route doing the shadowing
     */
    public function __construct(
        public string $rule,
        public Severity $severity,
        public string $message,
        public RouteData $route,
        public array $related = [],
    ) {}

    /**
     * @return array{rule: string, severity: string, message: string, route: string, related: list<string>}
     */
    public function toArray(): array
    {
        return [
            'rule' => $this->rule,
            'severity' => $this->severity->value,
            'message' => $this->message,
            'route' => $this->route->key(),
            'related' => array_map(fn (RouteData $route): string => $route->key(), $this->related),
        ];
    }

    /**
     * @return array{rule: string, severity: string, message: string, route: string, related: list<string>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
