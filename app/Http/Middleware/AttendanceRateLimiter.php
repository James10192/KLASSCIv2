<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use App\Models\ESBTPAttendanceCode;
use Carbon\Carbon;

class AttendanceRateLimiter
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->has('code')) {
            return $next($request);
        }

        $key = 'attendance_' . $request->ip() . '_' . auth()->id();

        // Check if user is blocked due to too many attempts
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Trop de tentatives. Veuillez réessayer dans {$seconds} secondes.",
                'blocked_until' => Carbon::now()->addSeconds($seconds)
            ], 429);
        }

        // Check if code exists and get attempts
        $code = ESBTPAttendanceCode::where('code', $request->code)
            ->where('expiration', '>', now())
            ->first();

        if ($code) {
            $attempts = $code->attempts()
                ->where('user_id', auth()->id())
                ->count();

            if ($attempts >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nombre maximum de tentatives atteint pour ce code.',
                    'attempts' => $attempts
                ], 429);
            }
        }

        // Add rate limiting hit
        RateLimiter::hit($key, 60); // Block for 1 minute after 3 attempts

        return $next($request);
    }
}
