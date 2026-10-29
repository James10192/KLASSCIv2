<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use App\Models\ESBTPDailyCode;
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
        $code = ESBTPDailyCode::where('code', $request->code)
            ->where('valid_until', '>', now())
            ->where('status', 'active')
            ->first();

        if ($code) {
            $attempts = \App\Models\ESBTPTeacherAttendance::where('daily_code_id', $code->id)
                ->where('teacher_id', auth()->id())
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
