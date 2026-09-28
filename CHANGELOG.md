# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

Everything below is planned for **3.0.0**. See [UPGRADE.md](UPGRADE.md).

### Changed (breaking)
- Requires PHP 8.3+ and Laravel 11, 12 or 13. Laravel 10 is no longer supported.
- The dashboard view now receives routes as `RouteData` arrays (`uri`, `methods`, ...). Re-publish customized views.

### Added
- `RouteData` and `RouteParameter` DTOs with methods, URI, name, domain, raw action, declared and resolved middleware, parameters with `where()` constraints, source file and line, API and fallback flags.
- `RouteScope::all()`, `api()`, `web()` and `filter()`.
- `RouteData::hasMethod()` and `hasMiddleware()`.
- Laravel 13 support, and CI coverage for it.

### Fixed
- A published `config/routescope.php` crashed the app on boot ("Target class [env] does not exist"), and broke `config:cache`, because it called `app()->environment()` before the environment was known. `enabled` now defaults to `null`, and the service provider applies the local/development fallback.
- `ROUTESCOPE_ENABLED` values such as `"false"` are parsed as booleans instead of being cast to `true`.

### Deprecated
- `RouteScope::getAllRoutes()`. It still returns the v2 format and will be removed in v4.

- Redesigned dashboard: a filterable list with All/API/Web views, routes grouped by path prefix in collapsible sections (or a flat list), expandable rows showing the action, file:line, parameters and resolved middleware, light/dark themes that follow the system, and a `/` shortcut.
- Laravel Boost's `_boost` routes are excluded by default.

### Removed
- Tailwind Play CDN and unpkg Lucide from the dashboard. It now uses inline CSS and SVG icons, and works offline and under a strict CSP.

### Security
- Escape route data in the dashboard to prevent XSS via route URIs, names or middleware.
- Outside the `local` environment the dashboard now requires the `viewRouteScope` gate to pass. **If you enable RouteScope on staging, define this gate or you will get a 403.**

### Added
- `middleware` config option (defaults to `['web']`).
- Dashboard shows route names and middleware, and searches them.
- Working "copy path" action, and "open" for GET routes without parameters.
- `routescope-views` publish tag (the old `views` tag still works).
- Source labels for `Route::view()` and `Route::redirect()` routes.
- Test suite using Orchestra Testbench.

### Fixed
- Package route is now named `routescope.index` instead of `index`, which could collide with app routes.
- Closures from cached routes (`route:cache`) are reported as `Closure` instead of `routes/web.php`.
- Routes using the `api` middleware group are categorized as API routes even without an `/api` prefix; `/api` itself is now an API route.
- Excluded patterns match whole path segments (`telescope` no longer hides `/telescopes`) and support `*` wildcards.
- Routes are sorted by path and then by HTTP method.
- Only a leading `App\` namespace is stripped from controller sources.
- Views are registered even when the dashboard is disabled.
- Removed dead "View code" / "Test endpoint" buttons.
- Fixed broken Rector and Peck configuration.

### Removed
- Unused `vlucas/phpdotenv` dependency.
- Hard-coded `version` in `composer.json` (versions come from git tags).

## [2.0.1]
## [2.0.0]
## [1.0.1]
## [1.0.0]
