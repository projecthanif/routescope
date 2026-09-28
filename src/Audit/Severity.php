<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Audit;

enum Severity: string
{
    case Error = 'error';
    case Warning = 'warning';

    public function rank(): int
    {
        return match ($this) {
            self::Error => 2,
            self::Warning => 1,
        };
    }
}
