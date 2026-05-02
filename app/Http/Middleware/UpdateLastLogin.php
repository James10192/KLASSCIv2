<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UpdateLastLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $now = Carbon::now();

            // last_login_at : update une fois par jour pour audit/historique.
            if (!$user->last_login_at || Carbon::parse($user->last_login_at)->toDateString() !== $now->toDateString()) {
                $user->last_login_at = $now;
            }

            // last_seen_at : throttled à 30s pour la présence "en ligne" sans tuer la DB.
            if (!$user->last_seen_at || Carbon::parse($user->last_seen_at)->lt($now->copy()->subSeconds(30))) {
                $user->last_seen_at = $now;
            }

            if ($user->isDirty(['last_login_at', 'last_seen_at'])) {
                $user->saveQuietly();
            }
        }

        return $next($request);
    }
} 