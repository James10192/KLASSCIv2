<?php

namespace Tests\Feature\Attendance;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendanceManualHours;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Services\ESBTP\ESBTPAbsenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualHoursClassScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_absence_service_keeps_manual_hours_isolated_by_class(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();
        $classeTroncCommun = ESBTPClasse::factory()->create();
        $classeSpecialite = ESBTPClasse::factory()->create();

        ESBTPAttendanceManualHours::create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'classe_id' => $classeTroncCommun->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'heures_absence_justifiees' => 2,
        ]);
        ESBTPAttendanceManualHours::create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'classe_id' => $classeSpecialite->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'heures_absence_justifiees' => 5,
        ]);

        $absenceService = app(ESBTPAbsenceService::class);

        $troncCommun = $absenceService->calculerDetailAbsences(
            $etudiant->id, $classeTroncCommun->id, '2026-01-01', '2026-06-30', $annee->id, 'semestre1'
        );
        $specialite = $absenceService->calculerDetailAbsences(
            $etudiant->id, $classeSpecialite->id, '2026-01-01', '2026-06-30', $annee->id, 'semestre1'
        );

        $this->assertSame(2.0, $troncCommun['justifiees']);
        $this->assertSame(5.0, $specialite['justifiees']);
        $this->assertSame([$matiere->id], $troncCommun['manual_matieres']);
        $this->assertSame([$matiere->id], $specialite['manual_matieres']);
    }
}
