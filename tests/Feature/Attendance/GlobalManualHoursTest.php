<?php

namespace Tests\Feature\Attendance;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendanceManualHours;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\User;
use App\Services\ESBTP\ESBTPAbsenceService;
use App\Services\ESBTP\ManualAttendanceHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GlobalManualHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('create_attendance', 'web');
        Cache::flush();
    }

    private function authUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create_attendance');
        $this->actingAs($user);

        return $user;
    }

    private function enableGlobalFlag(): void
    {
        SettingsHelper::setOrCreate('attendance_manual_hours_global_enabled', '1', 'attendance', 'boolean');
        Cache::forget('setting_attendance_manual_hours_global_enabled');
    }

    public function test_global_save_rejected_when_flag_off(): void
    {
        $this->authUser();
        $etudiant = ESBTPEtudiant::factory()->create();
        $classe = ESBTPClasse::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();

        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);

        $response = $this->postJson(route('esbtp.attendances.manual.store'), [
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'entries' => [
                ['etudiant_id' => $etudiant->id, 'heures_absence_justifiees' => 4],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, ESBTPAttendanceManualHours::count());
    }

    public function test_global_save_writes_single_row_with_null_matiere_when_flag_on(): void
    {
        $this->enableGlobalFlag();
        $user = $this->authUser();

        $etudiant = ESBTPEtudiant::factory()->create();
        $classe = ESBTPClasse::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);

        $service = app(ManualAttendanceHoursService::class);
        $count = $service->upsertBatch(
            [['etudiant_id' => $etudiant->id, 'heures_absence_justifiees' => 4, 'heures_absence_non_justifiees' => 2]],
            ['classe_id' => $classe->id, 'matiere_id' => null, 'annee_universitaire_id' => $annee->id, 'periode' => 'semestre1'],
            $user->id
        );

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('esbtp_attendance_manual_hours', [
            'etudiant_id' => $etudiant->id,
            'matiere_id' => null,
            'heures_absence_justifiees' => 4,
            'heures_absence_non_justifiees' => 2,
        ]);
    }

    public function test_global_and_per_matiere_coexist_in_bulletin_totals(): void
    {
        $this->enableGlobalFlag();
        $user = $this->authUser();

        $etudiant = ESBTPEtudiant::factory()->create();
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();

        ESBTPAttendanceManualHours::create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'heures_absence_justifiees' => 3,
            'heures_absence_non_justifiees' => 1,
        ]);

        ESBTPAttendanceManualHours::create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => null,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'heures_absence_justifiees' => 5,
            'heures_absence_non_justifiees' => 2,
        ]);

        $absenceService = app(ESBTPAbsenceService::class);

        $result = $absenceService->calculerDetailAbsences(
            $etudiant->id,
            $classe->id,
            null,
            null,
            $annee->id,
            'semestre1'
        );

        $this->assertEquals(8.0, $result['justifiees']);
        $this->assertEquals(3.0, $result['non_justifiees']);
        $this->assertTrue($result['has_global']);
    }

    public function test_par_matiere_does_not_ventilate_global_across_matieres(): void
    {
        $this->enableGlobalFlag();
        $user = $this->authUser();

        $etudiant = ESBTPEtudiant::factory()->create();
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();

        ESBTPAttendanceManualHours::create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'heures_absence_justifiees' => 3,
            'heures_absence_non_justifiees' => 1,
        ]);

        ESBTPAttendanceManualHours::create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => null,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'heures_absence_justifiees' => 10,
        ]);

        $absenceService = app(ESBTPAbsenceService::class);

        $result = $absenceService->calculerAbsencesParMatiere(
            $etudiant->id,
            $classe->id,
            null,
            null,
            $annee->id,
            'semestre1'
        );

        $this->assertArrayHasKey($matiere->id, $result['par_matiere']);
        $this->assertSame(3.0, (float) $result['par_matiere'][$matiere->id]['justifiees']);
        $this->assertSame(1.0, (float) $result['par_matiere'][$matiere->id]['non_justifiees']);
        $this->assertTrue($result['has_global']);
        $this->assertSame(10.0, $result['global']['justifiees']);
    }
}
