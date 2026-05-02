<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class UpdateLastLogin
{
    /**
     * Mémorise si la colonne last_seen_at existe sur le tenant courant
     * (cache process : évite un DESCRIBE par request). Permet la rétrocompat avec
     * les tenants où la migration `add_last_seen_at_to_users_table` n'est pas
     * encore exécutée.
     */
    private static ?bool $hasLastSeenColumn = null;

    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        try {
            $user = Auth::user();
            $now = Carbon::now();

            if (!$user->last_login_at || Carbon::parse($user->last_login_at)->toDateString() !== $now->toDateString()) {
                $user->last_login_at = $now;
            }

            if (self::lastSeenColumnExists()
                && (!$user->last_seen_at || Carbon::parse($user->last_seen_at)->lt($now->copy()->subSeconds(30)))) {
                $user->last_seen_at = $now;
            }

            $dirty = self::lastSeenColumnExists()
                ? $user->isDirty(['last_login_at', 'last_seen_at'])
                : $user->isDirty(['last_login_at']);

            if ($dirty) {
                $user->saveQuietly();
            }
        } catch (\Throwable $e) {
            // Defense en profondeur : un échec de tracking presence ne doit JAMAIS
            // bloquer une request prod. Loggé pour observation, request continue.
            \Log::warning('UpdateLastLogin failed: ' . $e->getMessage());
        }

        return $next($request);
    }

    private static function lastSeenColumnExists(): bool
    {
        if (self::$hasLastSeenColumn === null) {
            try {
                self::$hasLastSeenColumn = Schema::hasColumn('users', 'last_seen_at');
            } catch (\Throwable $e) {
                self::$hasLastSeenColumn = false;
            }
        }
        return self::$hasLastSeenColumn;
    }
} 