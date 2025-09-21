<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // DEBUG SPÉCIAL pour paywall-config
        if (str_contains($request->fullUrl(), 'paywall-config')) {
            \Log::error('🚨🚨🚨 REQUÊTE PAYWALL-CONFIG DÉTECTÉE', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route_name' => $request->route() ? $request->route()->getName() : 'NO_ROUTE',
                'middleware_stack' => $request->route() ? $request->route()->gatherMiddleware() : [],
                'user' => $request->user() ? $request->user()->email : 'guest',
            ]);
        }

        Log::info('Incoming Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'input' => $request->all(),
            'route' => $request->route() ? $request->route()->getName() : null
        ]);

        $response = $next($request);

        Log::info('Outgoing Response', [
            'status' => method_exists($response, 'status') ? $response->status() : $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'content' => method_exists($response, 'content') ? $response->content() : 'N/A'
        ]);

        return $response;
    }
}
