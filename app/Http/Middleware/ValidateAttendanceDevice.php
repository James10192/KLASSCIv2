<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use App\Models\ESBTPTeacherAttendance;

class ValidateAttendanceDevice
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->has('code')) {
            return $next($request);
        }

        $deviceFingerprint = $this->generateDeviceFingerprint($request);
        $ipAddress = $request->ip();

        // Check for suspicious IP changes
        $recentAttendance = ESBTPTeacherAttendance::where('teacher_id', auth()->id())
            ->latest()
            ->first();

        if ($recentAttendance) {
            if ($recentAttendance->ip_address !== $ipAddress) {
                \Log::warning('IP change detected for teacher attendance', [
                    'teacher_id' => auth()->id(),
                    'old_ip' => $recentAttendance->ip_address,
                    'new_ip' => $ipAddress,
                    'timestamp' => now()
                ]);
            }

            // Compare device fingerprints
            $oldDeviceInfo = json_decode($recentAttendance->device_info, true);
            if ($oldDeviceInfo && $oldDeviceInfo['fingerprint'] !== $deviceFingerprint) {
                \Log::warning('Device change detected for teacher attendance', [
                    'teacher_id' => auth()->id(),
                    'old_device' => $oldDeviceInfo,
                    'new_device' => $this->getDeviceInfo($request),
                    'timestamp' => now()
                ]);
            }
        }

        // Add device info to the request for later use
        $request->merge([
            'device_info' => $this->getDeviceInfo($request),
            'ip_address' => $ipAddress
        ]);

        return $next($request);
    }

    private function generateDeviceFingerprint(Request $request): string
    {
        $agent = new Agent();
        $device = [
            'platform' => $agent->platform(),
            'browser' => $agent->browser(),
            'device' => $agent->device(),
            'headers' => [
                'accept_language' => $request->header('Accept-Language'),
                'user_agent' => $request->userAgent()
            ]
        ];

        return hash('sha256', json_encode($device));
    }

    private function getDeviceInfo(Request $request): array
    {
        $agent = new Agent();
        return [
            'fingerprint' => $this->generateDeviceFingerprint($request),
            'platform' => $agent->platform(),
            'platform_version' => $agent->version($agent->platform()),
            'browser' => $agent->browser(),
            'browser_version' => $agent->version($agent->browser()),
            'device' => $agent->device(),
            'is_mobile' => $agent->isMobile(),
            'is_tablet' => $agent->isTablet(),
            'is_desktop' => $agent->isDesktop(),
            'headers' => [
                'accept_language' => $request->header('Accept-Language'),
                'user_agent' => $request->userAgent()
            ]
        ];
    }
}
