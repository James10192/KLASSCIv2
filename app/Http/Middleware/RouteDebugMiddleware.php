<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class RouteDebugMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Log route information
        $currentRoute = Route::current();
        $routeName = $currentRoute ? $currentRoute->getName() : 'unnamed';
        $routeAction = $currentRoute ? $currentRoute->getActionName() : 'unknown';

        Log::channel('routes')->debug('Route accessed', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'route_name' => $routeName,
            'action' => $routeAction,
            'parameters' => $request->route()->parameters(),
            'input' => $request->except(['password', 'password_confirmation']),
            'user_id' => $request->user() ? $request->user()->id : 'guest',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $next($request);
    }
}
