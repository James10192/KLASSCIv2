<?php

namespace Tests\Unit\Services;

use App\Enums\JustificationStatus;
use App\Models\ESBTPAttendance;
use App\Services\ESBTP\ESBTPAbsenceService;
use App\Services\ESBTP\ManualHoursResolver;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class ESBTPAbsenceServiceJustificationStatusTest extends TestCase
{
    public function test_pending_justification_with_timestamp_is_not_counted_as_excused(): void
    {
        $absence = new ESBTPAttendance();
        $absence->forceFill([
            'statut' => 'absent',
            'justified_at' => now(),
            'justification_status' => JustificationStatus::PENDING,
        ]);

        $this->assertFalse($this->isApprovedOrExcused($absence));
    }

    public function test_approved_or_excused_attendance_is_counted_as_excused(): void
    {
        $approved = new ESBTPAttendance();
        $approved->forceFill([
            'statut' => 'absent',
            'justification_status' => JustificationStatus::APPROVED,
        ]);

        $excused = new ESBTPAttendance();
        $excused->forceFill(['statut' => 'excuse']);

        $this->assertTrue($this->isApprovedOrExcused($approved));
        $this->assertTrue($this->isApprovedOrExcused($excused));
    }

    public function test_session_duration_preserves_fractional_hours(): void
    {
        $service = new ESBTPAbsenceService(new ManualHoursResolver());
        $method = new ReflectionMethod($service, 'durationInHours');

        $this->assertSame(1.5, $method->invoke(
            $service,
            Carbon::parse('08:00'),
            Carbon::parse('09:30')
        ));
    }

    private function isApprovedOrExcused(ESBTPAttendance $absence): bool
    {
        $service = new ESBTPAbsenceService(new ManualHoursResolver());
        $method = new ReflectionMethod($service, 'isApprovedOrExcused');

        return $method->invoke($service, $absence);
    }
}
