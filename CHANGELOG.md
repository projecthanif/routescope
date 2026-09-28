# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [3.0.0] - 2026-09-28

See [UPGRADE.md](UPGRADE.md) for upgrading from 2.x.

### Changed (breaking)
- Requires PHP 8.3+ and Laravel 11, 12 or 13. Laravel 10 is no longer supported.
- The dashboard view receives routes as `RouteData` arrays (`uri`, `methods`, ...) instead of `path`/`method`. Re-publish customized views.
- `enabled` in `config/routescope.php` defaults to `null` (meaning local and development only) instead of calling `app()`. Update the `enabled` line in a published config.

### Added
- `RouteData` and `RouteParameter` DTOs: one entry per route with methods, URI, name, domain, raw action, declared and resolved middleware, parameters with `where()` constraints, source file and line, and API and fallback flags. Includes `hasMethod()`, `hasMiddleware()`, `key()` and JSON serialization.
- `RouteScope::all()`, `api()`, `web()`, `filter()`, `globalMiddleware()` and `RouteScopeService::describe()`.
- `php artisan routescope:audit` with `--json` and `--fail-on=error|warning|never`, for use in CI. Rules: `missing-action`, `overridden-route`, `shadowed-route`, `duplicate-name` and `api-without-auth`. Configurable under `audit` (auth middleware, per-rule ignores, custom rules).
- `php artisan routescope:export {json|markdown|openapi} {--output=} {--only=api|web|all}`: JSON with every route field, Markdown tables, or an OpenAPI 3.1 skeleton (paths, operations, typed path parameters, optional parameters expanded, middleware under `x-middleware`).
- Redesigned dashboard:
  - A filterable list with All/API/Web views, grouped by path prefix in collapsible sections (or a flat list).
  - Expandable route details: action, file:line, name, parameters and the full middleware execution order (global middleware first, then route and controller middleware).
  - Filters: HTTP method chips, a middleware dropdown with usage counts, a domain dropdown when routes use domains, and "No auth" / "Unnamed" toggles.
  - Audit issues flagged on each route, with a header toggle to show only affected routes.
  - "Open in editor" links, configured with `ROUTESCOPE_EDITOR` or Laravel's `app.editor` (editor names, custom `href` templates, and `base_path` mapping for Docker/Sail).
  - View, search, filters and the expanded route kept in the URL, so filtered views and individual routes can be bookmarked and shared.
  - Light and dark themes that follow the system, and a `/` shortcut for search.
- Laravel 13 support, and CI across PHP 8.3/8.4 and Laravel 11/12/13.
- Laravel Boost's `_boost` routes are excluded by default.

### Fixed
- A published `config/routescope.php` crashed the app on boot ("Target class [env] does not exist"), and broke `config:cache`, because it called `app()->environment()` before the environment was known.
- `ROUTESCOPE_ENABLED` values such as `"false"` are parsed as booleans instead of being cast to `true`.
- Resolved middleware is sorted by middleware priority, matching the order Laravel runs it in, and expands groups and aliases in console commands too.
- The dashboard escapes quotes in attribute values, so a route name or source containing `"` can't add attributes.

### Deprecated
- `RouteScope::getAllRoutes()`. It still returns the v2 format and will be removed in v4.

### Removed
- Tailwind Play CDN and unpkg Lucide from the dashboard. It now uses inline CSS and SVG icons, and works offline and under a strict CSP.

## [2.1.0] - 2026-09-28

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
