<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class DebugHelper
{
    public static function logRouteInfo($route, $message = '')
    {
        if (!$route) return;

        Log::channel('routes')->debug($message ?: 'Route Information', [
            'name' => $route->getName(),
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'action' => $route->getActionName(),
            'middleware' => $route->middleware(),
            'parameters' => $route->parameters(),
            'wheres' => $route->wheres,
            'compiled' => $route->compiled() ? [
                'static_prefix' => $route->compiled()->getStaticPrefix(),
                'regex' => $route->compiled()->getRegex(),
            ] : null,
        ]);
    }

    public static function logQueryInfo($query, $bindings = [], $time = null)
    {
        Log::channel('queries')->debug('Query Information', [
            'sql' => $query,
            'bindings' => $bindings,
            'time' => $time,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
        ]);
    }

    public static function logDebug($message, $context = [])
    {
        Log::channel('debug')->debug($message, array_merge($context, [
            'memory' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
        ]));
    }
}
