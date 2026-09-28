<?php

declare(strict_types=1);

namespace Projecthanif\RouteScope\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class Authorize
{
    /**
     * Allow access in the local environment, otherwise defer to the "viewRouteScope" gate.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            return $next($request);
        }

        abort_unless(Gate::forUser($request->user())->check('viewRouteScope'), 403);

        return $next($request);
    }
}
