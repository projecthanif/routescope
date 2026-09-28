<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Data;

use JsonSerializable;

final readonly class RouteParameter implements JsonSerializable
{
    public function __construct(
        public string $name,
        public bool $optional,
        public ?string $pattern,
    ) {}

    /**
     * @return array{name: string, optional: bool, pattern: string|null}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'optional' => $this->optional,
            'pattern' => $this->pattern,
        ];
    }

    /**
     * @return array{name: string, optional: bool, pattern: string|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
