<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class BulletinClassRankRecalcTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequential_generation_recalculates_the_whole_class(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        $first = $this->makeStudentBulletin($classe, $annee, 16.0, 0.13);
        $second = $this->makeStudentBulletin($classe, $annee, 12.0, 0.00);

        $service = app(BulletinService::class);
        $service->calculerRang($first);
        $service->calculerRangsPourClasse($classe->id, $annee->id, 'semestre2');

        $this->assertSame(1, (int) $first->fresh()->rang);
        $this->assertSame(2, (int) $second->fresh()->rang);
        $this->assertSame(2, (int) $first->fresh()->effectif_classe);
    }

    public function test_preview_rank_uses_live_cohort_averages_when_bulletins_are_empty(): void
    {
        $user = User::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $matiere = ESBTPMatiere::factory()->create();
        $evaluation = ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre2',
            'coefficient' => 1,
            'bareme' => 20,
            'status' => 'completed',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $first = $this->makeStudentWithLiveAverage($classe, $annee, $evaluation, $matiere, $user, 16.0);
        $second = $this->makeStudentWithLiveAverage($classe, $annee, $evaluation, $matiere, $user, 12.0);

        $service = app(BulletinService::class);
        $method = new ReflectionMethod($service, 'collectSemesterAveragesForClasse');
        $method->setAccessible(true);
        $averages = $method->invoke($service, $classe->id, $annee->id, 'semestre2');
        $rankMethod = new ReflectionMethod($service, 'rankAmongAverages');
        $rankMethod->setAccessible(true);

        $this->assertSame(1, $rankMethod->invoke($service, $averages, $averages[$first]));
        $this->assertSame(2, $rankMethod->invoke($service, $averages, $averages[$second]));
    }

    private function makeStudentWithLiveAverage(
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        ESBTPEvaluation $evaluation,
        ESBTPMatiere $matiere,
        User $user,
        float $note
    ): int {
        $etudiant = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        ESBTPNote::create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'note' => $note,
            'semestre' => 2,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return (int) $etudiant->id;
    }

    private function makeStudentBulletin(ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, float $moyenne, float $assiduite): ESBTPBulletin
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        return ESBTPBulletin::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre2',
            'moyenne_generale' => $moyenne,
            'note_assiduite' => $assiduite,
        ]);
    }
}
