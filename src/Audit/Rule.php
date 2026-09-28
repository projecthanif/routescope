<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Audit;

/**
 * An audit check. Rules are resolved from the container, so they can type-hint dependencies.
 */
interface Rule
{
    /**
     * Identifier used in output and in the `audit.ignore` config, e.g. "shadowed-route".
     */
    public function id(): string;

    /**
     * @return iterable<Issue>
     */
    public function check(): iterable;
}
