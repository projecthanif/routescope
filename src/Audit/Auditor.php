<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Audit;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Projecthanif\RouteScope\Audit\Rules\ApiWithoutAuth;
use Projecthanif\RouteScope\Audit\Rules\DuplicateName;
use Projecthanif\RouteScope\Audit\Rules\MissingAction;
use Projecthanif\RouteScope\Audit\Rules\OverriddenRoute;
use Projecthanif\RouteScope\Audit\Rules\ShadowedRoute;

final readonly class Auditor
{
    public const array DEFAULT_RULES = [
        MissingAction::class,
        OverriddenRoute::class,
        ShadowedRoute::class,
        DuplicateName::class,
        ApiWithoutAuth::class,
    ];

    public function __construct(private Container $container) {}

    /**
     * Run every configured rule, dropping ignored issues. Errors come first, then by URI.
     *
     * @return Collection<int, Issue>
     */
    public function audit(): Collection
    {
        $issues = [];

        foreach ($this->rules() as $rule) {
            foreach ($rule->check() as $issue) {
                if (! $this->isIgnored($issue)) {
                    $issues[] = $issue;
                }
            }
        }

        usort($issues, fn (Issue $a, Issue $b): int => [$b->severity->rank(), $a->route->uri, $a->rule]
            <=> [$a->severity->rank(), $b->route->uri, $b->rule]);

        return collect($issues);
    }

    /**
     * @return list<Rule>
     */
    private function rules(): array
    {
        $classes = config('routescope.audit.rules', self::DEFAULT_RULES);

        return array_map(function (mixed $class): Rule {
            $rule = is_string($class) ? $this->container->make($class) : $class;

            if (! $rule instanceof Rule) {
                throw new InvalidArgumentException('Audit rules must implement '.Rule::class.'.');
            }

            return $rule;
        }, array_values(is_array($classes) ? $classes : self::DEFAULT_RULES));
    }

    /**
     * An issue is ignored when its route's URI or name matches a pattern listed for its rule, or under "*".
     */
    private function isIgnored(Issue $issue): bool
    {
        $ignore = config('routescope.audit.ignore', []);

        if (! is_array($ignore)) {
            return false;
        }

        $patterns = array_values(array_filter(
            [...(array) ($ignore[$issue->rule] ?? []), ...(array) ($ignore['*'] ?? [])],
            is_string(...),
        ));

        if ($patterns === []) {
            return false;
        }

        $patterns = array_map(fn (string $pattern): string => trim($pattern, '/'), $patterns);

        return Str::is($patterns, trim($issue->route->uri, '/'))
            || ($issue->route->name !== null && Str::is($patterns, $issue->route->name));
    }
}
