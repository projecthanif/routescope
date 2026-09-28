<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Console;

use Illuminate\Console\Command;
use Projecthanif\RouteScope\Audit\Auditor;
use Projecthanif\RouteScope\Audit\Issue;
use Projecthanif\RouteScope\Audit\Severity;

final class AuditCommand extends Command
{
    protected $signature = 'routescope:audit
        {--json : Output the issues as JSON}
        {--fail-on=warning : Exit with an error on "error" issues, on "warning" and above, or "never"}';

    protected $description = 'Check your routes for problems such as shadowed routes and unauthenticated API endpoints';

    public function handle(Auditor $auditor): int
    {
        $failOn = $this->option('fail-on');

        if (! in_array($failOn, ['error', 'warning', 'never'], true)) {
            $this->components->error('The --fail-on option must be "error", "warning" or "never".');

            return self::INVALID;
        }

        $issues = $auditor->audit();

        if ($this->option('json')) {
            $this->line((string) json_encode($issues->values(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } elseif ($issues->isEmpty()) {
            $this->components->info('No route issues found.');
        } else {
            $this->renderIssues($issues->all());
        }

        $threshold = match ($failOn) {
            'error' => Severity::Error->rank(),
            'warning' => Severity::Warning->rank(),
            default => PHP_INT_MAX,
        };

        return $issues->contains(fn (Issue $issue): bool => $issue->severity->rank() >= $threshold)
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @param  array<int, Issue>  $issues
     */
    private function renderIssues(array $issues): void
    {
        $this->newLine();

        foreach ($issues as $issue) {
            $label = $issue->severity === Severity::Error ? '<fg=red;options=bold>ERROR</>' : '<fg=yellow;options=bold>WARN</> ';

            $this->line(sprintf(
                '  %s  <options=bold>%s %s</>  <fg=gray>%s</>',
                $label,
                implode('|', $issue->route->methods),
                $issue->route->uri,
                $issue->rule,
            ));
            $this->line('         '.$issue->message);
            $this->newLine();
        }

        $errors = count(array_filter($issues, fn (Issue $issue): bool => $issue->severity === Severity::Error));
        $warnings = count($issues) - $errors;

        $this->line(sprintf('  <options=bold>%d %s, %d %s</>', $errors, $errors === 1 ? 'error' : 'errors', $warnings, $warnings === 1 ? 'warning' : 'warnings'));
        $this->newLine();
    }
}
