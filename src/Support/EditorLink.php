<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Support;

/**
 * Builds "open in editor" URLs using the same `editor` config format as Laravel's own
 * dump() source links: an editor name ("phpstorm"), or an array with `name`, a custom
 * `href` template with {file} and {line}, and an optional `base_path` for when the app
 * runs in a container and the editor sees a different path.
 */
final readonly class EditorLink
{
    /**
     * Same templates as Laravel's Illuminate\Foundation\Concerns\ResolvesDumpSource.
     */
    public const array HREFS = [
        'antigravity' => 'antigravity://file/{file}:{line}',
        'atom' => 'atom://core/open/file?filename={file}&line={line}',
        'cursor' => 'cursor://file/{file}:{line}',
        'emacs' => 'emacs://open?url=file://{file}&line={line}',
        'fleet' => 'fleet://open?file={file}&line={line}',
        'idea' => 'idea://open?file={file}&line={line}',
        'kiro' => 'kiro://file/{file}:{line}',
        'macvim' => 'mvim://open/?url=file://{file}&line={line}',
        'neovim' => 'nvim://open?url=file://{file}&line={line}',
        'netbeans' => 'netbeans://open/?f={file}:{line}',
        'nova' => 'nova://core/open/file?filename={file}&line={line}',
        'phpstorm' => 'phpstorm://open?file={file}&line={line}',
        'sublime' => 'subl://open?url=file://{file}&line={line}',
        'textmate' => 'txmt://open?url=file://{file}&line={line}',
        'trae' => 'trae://file/{file}:{line}',
        'vscode' => 'vscode://file/{file}:{line}',
        'vscode-insiders' => 'vscode-insiders://file/{file}:{line}',
        'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/{file}:{line}',
        'vscode-remote' => 'vscode://vscode-remote/{file}:{line}',
        'vscodium' => 'vscodium://file/{file}:{line}',
        'windsurf' => 'windsurf://file/{file}:{line}',
        'xdebug' => 'xdebug://{file}@{line}',
        'zed' => 'zed://file/{file}:{line}',
    ];

    /**
     * @param  string|array<array-key, mixed>|null  $editor
     */
    public function __construct(
        private string|array|null $editor,
        private string $basePath,
    ) {}

    /**
     * Uses `routescope.editor`, falling back to Laravel's `app.editor`.
     */
    public static function fromConfig(): self
    {
        $editor = config('routescope.editor') ?? config('app.editor');

        return new self(is_string($editor) || is_array($editor) ? $editor : null, base_path());
    }

    /**
     * @param  string|null  $file  Absolute, or relative to the app base path
     */
    public function url(?string $file, ?int $line): ?string
    {
        $href = $this->href();

        if ($href === null || $file === null) {
            return null;
        }

        $base = rtrim($this->basePath, '/');
        $path = str_starts_with($file, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $file) === 1 ? $file : $base.'/'.$file;

        $mapped = $this->editorBasePath();

        if ($mapped !== null && str_starts_with($path, $base.'/')) {
            $path = rtrim($mapped, '/').substr($path, strlen($base));
        }

        // Encode each segment so paths with spaces survive, while keeping the slashes
        $encoded = implode('/', array_map(rawurlencode(...), explode('/', $path)));

        return str_replace(['{file}', '{line}'], [$encoded, (string) ($line ?? 1)], $href);
    }

    private function href(): ?string
    {
        $editor = $this->editor;

        if (is_array($editor) && isset($editor['href']) && is_string($editor['href'])) {
            return $editor['href'];
        }

        $name = is_array($editor) ? ($editor['name'] ?? null) : $editor;

        if (! is_string($name) || $name === '') {
            return null;
        }

        return self::HREFS[$name] ?? $name.'://open?file={file}&line={line}';
    }

    private function editorBasePath(): ?string
    {
        $basePath = is_array($this->editor) ? ($this->editor['base_path'] ?? null) : null;

        return is_string($basePath) && $basePath !== '' ? $basePath : null;
    }
}
