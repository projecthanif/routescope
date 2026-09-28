# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

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
