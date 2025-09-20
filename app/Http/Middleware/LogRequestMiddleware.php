<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestMiddleware
{
    public function handle(Request $request, Closure $next)
    {
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
