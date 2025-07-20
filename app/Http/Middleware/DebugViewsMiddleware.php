<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Throwable;

class DebugViewsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Capturer les erreurs de vue
        View::composer('*', function ($view) {
            try {
                $data = $view->getData();
                foreach ($data as $key => $value) {
                    if (is_object($value) && method_exists($value, 'is_current')) {
                        if (is_null($value)) {
                            \Log::error("Variable '{$key}' est null dans la vue: " . $view->getName());
                        }
                    }
                    if ($key === 'anneesUniversitaires' && is_iterable($value)) {
                        foreach ($value as $index => $item) {
                            if (is_null($item)) {
                                \Log::error("Item {$index} est null dans anneesUniversitaires dans la vue: " . $view->getName());
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                \Log::error("Erreur lors du debug de vue: " . $e->getMessage() . " dans " . $view->getName());
            }
        });

        return $next($request);
    }
}