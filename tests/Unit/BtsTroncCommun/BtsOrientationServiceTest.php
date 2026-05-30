<?php

namespace Tests\Unit\BtsTroncCommun;

use App\Domain\BtsTroncCommun\BtsOrientationService;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BtsOrientationServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_orients_the_same_inscription_into_a_specialisation_phase(): void
    {
        $service = app(BtsOrientationService::class);
        [$inscription, $targetClasse, $targetMap] = $this->makeOrientationFixture();

        $service->ensureInitialPhase($inscription);
        $updated = $service->orient($inscription->fresh(['filiere', 'classe.orientationTargets', 'phases']), $targetClasse->id);

        $this->assertSame($targetClasse->id, $updated->classe_id);
        $this->assertCount(2, $updated->phases);
        $this->assertSame($targetMap->id, $updated->phases->last()->orientation_target_id);
        $this->assertSame('specialisation', $updated->phases->last()->type_phase);
        $this->assertFalse((bool) $updated->phases->first()->is_active);
    }

    private function makeOrientationFixture(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $sourceClasse = ESBTPClasse::factory()->create(['filiere_id' => $tcFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $targetClasse = ESBTPClasse::factory()->create(['filiere_id' => $specFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $target = ESBTPClasseOrientationTarget::create([
            'source_classe_id' => $sourceClasse->id,
            'target_classe_id' => $targetClasse->id,
            'semestre_activation' => 2,
            'is_active' => true,
        ]);

        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $sourceClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        return [$inscription, $targetClasse, $target];
    }
}
