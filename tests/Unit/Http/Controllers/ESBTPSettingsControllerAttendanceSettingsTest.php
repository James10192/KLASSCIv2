<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\ESBTP\ESBTPSettingsController;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ESBTPSettingsControllerAttendanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_settings_describe_hour_based_thresholds(): void
    {
        $controller = new ESBTPSettingsController();
        $method = new ReflectionMethod($controller, 'ensureAttendanceNoteSettings');
        $method->invoke($controller);

        $this->assertSame('0.13', Setting::where('key', 'attendance_note_zero_unjustified')->value('value'));
        $this->assertSame('0.00', Setting::where('key', 'attendance_note_one_unjustified')->value('value'));
        $this->assertStringContainsString('heure', Setting::where('key', 'attendance_note_one_unjustified')->value('description'));
        $this->assertStringContainsString('5 heures', Setting::where('key', 'attendance_note_five_or_more_unjustified')->value('description'));
    }
}
