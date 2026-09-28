<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Projecthanif\RouteScope\Export\Exporter;
use Projecthanif\RouteScope\Export\JsonExporter;
use Projecthanif\RouteScope\Export\MarkdownExporter;
use Projecthanif\RouteScope\Export\OpenApiExporter;
use Projecthanif\RouteScope\Services\RouteScopeService;

final class ExportCommand extends Command
{
    protected $signature = 'routescope:export
        {format=json : json, markdown or openapi}
        {--output= : Write to this file instead of standard output}
        {--only= : Export only "api" or "web" routes (openapi defaults to api)}';

    protected $description = 'Export your routes as JSON, Markdown or an OpenAPI skeleton';

    public function handle(RouteScopeService $routeScope, Filesystem $files): int
    {
        $format = $this->argument('format');
        $only = $this->option('only') ?? ($format === 'openapi' ? 'api' : null);

        $exporter = match ($format) {
            'json' => new JsonExporter,
            'markdown', 'md' => new MarkdownExporter,
            'openapi' => new OpenApiExporter($this->appName(), '1.0.0'),
            default => null,
        };

        if (! $exporter instanceof Exporter) {
            $this->components->error('The format must be "json", "markdown" or "openapi".');

            return self::INVALID;
        }

        $routes = match ($only) {
            'api' => $routeScope->api(),
            'web' => $routeScope->web(),
            null, 'all' => $routeScope->all(),
            default => null,
        };

        if (! $routes instanceof Collection) {
            $this->components->error('The --only option must be "api", "web" or "all".');

            return self::INVALID;
        }

        $contents = $exporter->export($routes);
        $output = $this->option('output');

        if (! is_string($output) || $output === '') {
            $this->output->write($contents);

            return self::SUCCESS;
        }

        $files->ensureDirectoryExists(dirname($output));
        $files->put($output, $contents);

        $this->components->info(sprintf('Exported %d routes to [%s].', $routes->count(), $output));

        return self::SUCCESS;
    }

    private function appName(): string
    {
        $name = config('app.name');

        return is_string($name) && $name !== '' ? $name : 'API';
    }
}
