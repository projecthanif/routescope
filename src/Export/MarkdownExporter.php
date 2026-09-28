<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Export;

use Illuminate\Support\Collection;
use Projecthanif\RouteScope\Data\RouteData;

/**
 * A table of routes per section (API, then web), for documentation and pull requests.
 */
final class MarkdownExporter implements Exporter
{
    public function export(Collection $routes): string
    {
        $sections = array_filter([
            'API Routes' => $routes->filter(fn (RouteData $route): bool => $route->isApi),
            'Web Routes' => $routes->reject(fn (RouteData $route): bool => $route->isApi),
        ], fn (Collection $section): bool => $section->isNotEmpty());

        $markdown = "# Routes\n";

        if ($sections === []) {
            return $markdown."\nNo routes.\n";
        }

        foreach ($sections as $title => $section) {
            $markdown .= "\n## {$title}\n\n";
            $markdown .= "| Method | URI | Name | Action | Middleware |\n";
            $markdown .= "|---|---|---|---|---|\n";

            foreach ($section as $route) {
                $markdown .= sprintf(
                    "| %s | %s | %s | %s | %s |\n",
                    implode(', ', $route->methods),
                    $this->code($route->domain.$route->uri),
                    $route->name === null ? '' : $this->code($route->name),
                    $this->code($route->action),
                    implode(', ', array_map($this->code(...), $route->middleware)),
                );
            }
        }

        return $markdown;
    }

    /**
     * Inline code, with pipes escaped so they don't break the table.
     */
    private function code(string $value): string
    {
        return '`'.str_replace('|', '\|', $value).'`';
    }
}
