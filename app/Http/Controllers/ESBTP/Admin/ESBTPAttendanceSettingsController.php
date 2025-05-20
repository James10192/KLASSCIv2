<?php

namespace App\Http\Controllers\ESBTP\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Models\ESBTPAttendanceSettings;

class ESBTPAttendanceSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:superAdmin']);
    }

    public function index()
    {
        $settings = Cache::remember('attendance_settings', 3600, function () {
            return ESBTPAttendanceSettings::first() ?? $this->getDefaultSettings();
        });

        return view('esbtp.admin.attendance.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings.code_expiration_hours' => 'required|integer|min:1|max:72',
            'settings.max_attempts' => 'required|integer|min:1|max:10',
            'settings.early_marking_minutes' => 'required|integer|min:0|max:60',
            'settings.late_marking_minutes' => 'required|integer|min:0|max:120',
            'settings.notify_admin_on_failure' => 'boolean',
            'settings.notify_teacher_reminder' => 'boolean',
            'settings.enforce_ip_validation' => 'boolean',
            'settings.enforce_device_validation' => 'boolean',
        ]);

        $settings = ESBTPAttendanceSettings::first() ?? new ESBTPAttendanceSettings();
        $settings->settings = $request->settings;
        $settings->save();

        // Clear the cache
        Cache::forget('attendance_settings');

        return redirect()->route('esbtp.admin.attendance.settings')
            ->with('success', 'Les paramètres ont été mis à jour avec succès.');
    }

    private function getDefaultSettings()
    {
        return [
            'code_expiration_hours' => 24,
            'max_attempts' => 3,
            'early_marking_minutes' => 15,
            'late_marking_minutes' => 30,
            'notify_admin_on_failure' => true,
            'notify_teacher_reminder' => true,
            'enforce_ip_validation' => true,
            'enforce_device_validation' => true,
        ];
    }
}
