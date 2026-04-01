<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequests
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('Incoming request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'input' => $request->except(['password', 'password_confirmation', 'current_password', '_token']),
            'route' => $request->route() ? $request->route()->getName() : null,
        ]);

        $response = $next($request);

        return $response;
    }
}
