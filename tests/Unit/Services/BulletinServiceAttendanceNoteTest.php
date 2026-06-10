<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\Setting;
use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Domain\BtsTroncCommun\BtsBulletinCohortResolver;
use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Services\BulletinService;
use App\Services\ESBTP\ESBTPAbsenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulletinServiceAttendanceNoteTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(?ESBTPAbsenceService $absenceService = null): BulletinService
    {
        $absenceService ??= \Mockery::mock(ESBTPAbsenceService::class);

        return new BulletinService(
            $absenceService,
            new BtsAnnualClassMapResolver(new BtsPhaseResolver()),
            new BtsBulletinCohortResolver(new BtsAnnualClassMapResolver(new BtsPhaseResolver()))
        );
    }

    private function seedAttendanceSettings(string $enabled = '1'): void
    {
        Setting::updateOrCreate(['key' => 'bulletin_show_attendance_note'], [
            'value' => $enabled,
            'type' => 'string',
            'group' => 'bulletin',
            'category' => 'bulletin',
            'is_active' => true,
        ]);

        Setting::updateOrCreate(['key' => 'attendance_note_zero_unjustified'], [
            'value' => '0.25',
            'type' => 'float',
            'group' => 'bulletin',
            'category' => 'bulletin',
            'is_active' => true,
        ]);

        Setting::updateOrCreate(['key' => 'attendance_note_one_unjustified'], [
            'value' => '0.05',
            'type' => 'float',
            'group' => 'bulletin',
            'category' => 'bulletin',
            'is_active' => true,
        ]);

        Setting::updateOrCreate(['key' => 'attendance_note_two_or_more_unjustified'], [
            'value' => '-0.40',
            'type' => 'float',
            'group' => 'bulletin',
            'category' => 'bulletin',
            'is_active' => true,
        ]);

        Setting::clearCache();
    }

    public function test_resolve_attendance_note_uses_configured_scale(): void
    {
        $this->seedAttendanceSettings('1');
        $service = $this->makeService();

        $this->assertSame(0.25, $service->resolveAttendanceNote(0, 0));
        $this->assertSame(0.05, $service->resolveAttendanceNote(0, 1));
        $this->assertSame(0.05, $service->resolveAttendanceNote(0, 1.5));
        $this->assertSame(-0.4, $service->resolveAttendanceNote(0, 2));
    }

    public function test_resolve_attendance_note_returns_zero_when_toggle_is_disabled(): void
    {
        $this->seedAttendanceSettings('0');
        $service = $this->makeService();

        $this->assertSame(0.0, $service->resolveAttendanceNote(0, 0));
        $this->assertSame(0.0, $service->resolveAttendanceNote(0, 3));
    }

    public function test_effective_bulletin_average_ignores_attendance_when_toggle_is_disabled(): void
    {
        $this->seedAttendanceSettings('0');
        $service = $this->makeService();

        $bulletin = new ESBTPBulletin([
            'moyenne_generale' => 12.5,
            'note_assiduite' => 0.75,
        ]);

        $this->assertSame(12.5, $service->getEffectiveBulletinAverage($bulletin));
    }

    public function test_calculate_effective_attendance_note_for_student_uses_absence_service_and_period(): void
    {
        $this->seedAttendanceSettings('1');
        $annee = ESBTPAnneeUniversitaire::factory()->create();

        $absenceService = \Mockery::mock(ESBTPAbsenceService::class);
        $absenceService
            ->shouldReceive('calculerDetailAbsences')
            ->once()
            ->with(10, 20, $annee->date_debut ?? null, $annee->date_fin ?? null, $annee->id, 'annuel')
            ->andReturn([
                'justifiees' => 0,
                'non_justifiees' => 2,
            ]);

        $service = $this->makeService($absenceService);

        $this->assertSame(-0.4, $service->calculateEffectiveAttendanceNoteForStudent(10, 20, $annee->id, 'annuel'));
    }
}
