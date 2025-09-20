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
            'headers' => $request->headers->all(),
            'input' => $request->all(),
            'route' => $request->route() ? [
                'name' => $request->route()->getName(),
                'parameters' => $request->route()->parameters(),
                'methods' => $request->route()->methods(),
            ] : null,
        ]);

        $response = $next($request);

        Log::info('Response', [
            'status' => method_exists($response, 'status') ? $response->status() : $response->getStatusCode(),
            'headers' => $response->headers->all(),
        ]);

        return $response;
    }
}
