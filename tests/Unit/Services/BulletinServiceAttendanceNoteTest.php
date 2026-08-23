<?php

namespace Tests\Unit\Services;

use App\Domain\BtsTroncCommun\ClasseOuvertureResolver;
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
            new BtsAnnualClassMapResolver(new BtsPhaseResolver(), new ClasseOuvertureResolver()),
            new BtsBulletinCohortResolver(new BtsAnnualClassMapResolver(new BtsPhaseResolver(), new ClasseOuvertureResolver())),
            new \App\Domain\BtsTroncCommun\BtsClassCohortCounter(new BtsPhaseResolver()),
            new ClasseOuvertureResolver()
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

        foreach ([
            'attendance_note_two_unjustified' => '-0.20',
            'attendance_note_three_to_four_unjustified' => '-0.60',
            'attendance_note_five_or_more_unjustified' => '-0.80',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], [
                'value' => $value,
                'type' => 'float',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'is_active' => true,
            ]);
        }

        Setting::clearCache();
    }

    public function test_resolve_attendance_note_uses_configured_hour_scale(): void
    {
        $this->seedAttendanceSettings('1');
        $service = $this->makeService();

        $this->assertSame(0.25, $service->resolveAttendanceNote(0, 0));
        $this->assertSame(0.05, $service->resolveAttendanceNote(0, 1));
        $this->assertSame(0.05, $service->resolveAttendanceNote(0, 1.5));
        $this->assertSame(-0.2, $service->resolveAttendanceNote(0, 2));
        $this->assertSame(-0.2, $service->resolveAttendanceNote(0, 2.5));
        $this->assertSame(-0.6, $service->resolveAttendanceNote(0, 3));
        $this->assertSame(-0.6, $service->resolveAttendanceNote(0, 4.5));
        $this->assertSame(-0.8, $service->resolveAttendanceNote(0, 5));
    }

    public function test_resolve_attendance_note_awards_bonus_only_when_total_hours_are_zero(): void
    {
        $this->seedAttendanceSettings('1');
        $service = $this->makeService();

        $this->assertSame(0.05, $service->resolveAttendanceNote(0.5, 0));
        $this->assertSame(0.05, $service->resolveAttendanceNote(2, 1));
    }

    public function test_legacy_attendance_calculator_delegates_to_configured_hour_scale(): void
    {
        $this->seedAttendanceSettings('1');
        $service = $this->makeService();

        $this->assertSame(-0.2, $service->calculerNoteAssiduite(0, 2.5));
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

        // Le service re-hydrate l'année via ESBTPAnneeUniversitaire::find(), donc les
        // dates passées sont des instances Carbon DISTINCTES de celles du modèle factory.
        // Mockery compare les objets par identité : on matche donc les dates par valeur
        // (string) via Mockery::on, tout en vérifiant strictement les IDs et la période.
        $expectedDebut = optional($annee->date_debut)->toDateString();
        $expectedFin = optional($annee->date_fin)->toDateString();

        $absenceService = \Mockery::mock(ESBTPAbsenceService::class);
        $absenceService
            ->shouldReceive('calculerDetailAbsences')
            ->once()
            ->with(
                10,
                20,
                \Mockery::on(fn ($d) => optional($d)->toDateString() === $expectedDebut),
                \Mockery::on(fn ($d) => optional($d)->toDateString() === $expectedFin),
                $annee->id,
                'annuel'
            )
            ->andReturn([
                'justifiees' => 0,
                'non_justifiees' => 2,
            ]);

        $service = $this->makeService($absenceService);

        $this->assertSame(-0.2, $service->calculateEffectiveAttendanceNoteForStudent(10, 20, $annee->id, 'annuel'));
    }
}
