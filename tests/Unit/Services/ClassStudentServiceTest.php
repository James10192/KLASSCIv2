<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use App\Services\ClassStudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassStudentServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_blocks_removing_a_student_with_an_active_specialisation(): void
    {
        [$inscription, $specialisationClasse] = $this->makeActiveSpecialisationFixture();

        $result = app(ClassStudentService::class)->removeStudents(
            $specialisationClasse,
            [$inscription->etudiant_id]
        );

        $this->assertSame(0, $result['removed']);
        $this->assertStringContainsString('workflow de correction de spécialisation', $result['errors'][0]);
        $this->assertSame($specialisationClasse->id, $inscription->fresh()->classe_id);
    }

    /** @test */
    public function it_restores_a_missing_primary_pointer_only_when_the_target_matches_the_active_specialisation(): void
    {
        [$inscription, $specialisationClasse] = $this->makeActiveSpecialisationFixture();
        $inscription->update(['classe_id' => null]);

        $result = app(ClassStudentService::class)->addStudents(
            $specialisationClasse,
            [$inscription->etudiant_id]
        );

        $updated = $inscription->fresh();

        $this->assertSame(1, $result['added']);
        $this->assertSame($specialisationClasse->id, $updated->classe_id);
        $this->assertSame($specialisationClasse->filiere_id, $updated->filiere_id);
    }

    private function makeActiveSpecialisationFixture(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true]);
        $specialisationFiliere = ESBTPFiliere::factory()->create([
            'parent_id' => $tcFiliere->id,
            'is_tronc_commun' => false,
        ]);
        $specialisationClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $specialisationFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specialisationFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specialisationClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => ESBTPInscriptionPhase::TYPE_SPECIALISATION,
            'classe_id' => $specialisationClasse->id,
            'filiere_id' => $specialisationFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
            'date_activation' => now(),
        ]);

        return [$inscription, $specialisationClasse];
    }
}
