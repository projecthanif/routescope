<?php

declare(strict_types=1);

use Projecthanif\RouteScope\Support\EditorLink;

it('builds links for known editors', function (string $editor, string $expected): void {
    $link = new EditorLink($editor, '/var/www/app');

    expect($link->url('app/Http/Controllers/UserController.php', 42))->toBe($expected);
})->with([
    ['phpstorm', 'phpstorm://open?file=/var/www/app/app/Http/Controllers/UserController.php&line=42'],
    ['vscode', 'vscode://file//var/www/app/app/Http/Controllers/UserController.php:42'],
    ['cursor', 'cursor://file//var/www/app/app/Http/Controllers/UserController.php:42'],
    ['zed', 'zed://file//var/www/app/app/Http/Controllers/UserController.php:42'],
    ['unknown', 'unknown://open?file=/var/www/app/app/Http/Controllers/UserController.php&line=42'],
]);

it('accepts the array format with a custom href and base path mapping', function (): void {
    $link = new EditorLink(['href' => 'custom://{file}#{line}', 'base_path' => '/Users/me/code/app/'], '/var/www/app/');

    expect($link->url('routes/web.php', 3))->toBe('custom:///Users/me/code/app/routes/web.php#3')
        // Absolute paths outside the app are left alone
        ->and($link->url('/opt/vendor/Thing.php', 1))->toBe('custom:///opt/vendor/Thing.php#1')
        ->and((new EditorLink(['name' => 'vscode'], '/app'))->url('a.php', 1))->toBe('vscode://file//app/a.php:1');
});

it('keeps absolute and windows paths, encodes spaces, and defaults the line', function (): void {
    $link = new EditorLink('phpstorm', '/var/www/app');

    expect($link->url('/var/www/app/My Files/A.php', null))->toBe('phpstorm://open?file=/var/www/app/My%20Files/A.php&line=1')
        ->and($link->url('C:\\sites\\app\\A.php', 5))->toBe('phpstorm://open?file=C%3A%5Csites%5Capp%5CA.php&line=5');
});

it('returns null without an editor or a file', function (): void {
    expect((new EditorLink(null, '/app'))->url('a.php', 1))->toBeNull()
        ->and((new EditorLink('', '/app'))->url('a.php', 1))->toBeNull()
        ->and((new EditorLink(['base_path' => '/x'], '/app'))->url('a.php', 1))->toBeNull()
        ->and((new EditorLink(['name' => 'vscode', 'base_path' => ''], '/app'))->url('a.php', 1))->toBe('vscode://file//app/a.php:1')
        ->and((new EditorLink('vscode', '/app'))->url(null, 1))->toBeNull();
});

it('reads routescope.editor, falling back to app.editor', function (): void {
    config(['routescope.editor' => null, 'app.editor' => 'zed']);
    expect(EditorLink::fromConfig()->url('/a.php', 1))->toBe('zed://file//a.php:1');

    config(['routescope.editor' => 'cursor']);
    expect(EditorLink::fromConfig()->url('/a.php', 1))->toBe('cursor://file//a.php:1');

    config(['routescope.editor' => null, 'app.editor' => 123]);
    expect(EditorLink::fromConfig()->url('/a.php', 1))->toBeNull();
});
