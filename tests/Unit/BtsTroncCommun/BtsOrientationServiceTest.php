<?php

namespace Tests\Unit\BtsTroncCommun;

use App\Domain\BtsTroncCommun\BtsOrientationService;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
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

    /** @test */
    public function it_removes_tronc_commun_phases_when_class_changes_to_non_tc(): void
    {
        $service = app(BtsOrientationService::class);
        [$inscription] = $this->makeOrientationFixture();
        $service->ensureInitialPhase($inscription);

        $nonTcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false]);
        $nonTcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $nonTcFiliere->id,
            'niveau_etude_id' => $inscription->niveau_id,
            'annee_universitaire_id' => $inscription->annee_universitaire_id,
        ]);

        $inscription->update([
            'classe_id' => $nonTcClasse->id,
            'filiere_id' => $nonTcClasse->filiere_id,
        ]);

        $updated = $service->syncAfterClassChange($inscription, $nonTcClasse);

        $this->assertCount(0, $updated->phases);
    }

    /** @test */
    public function it_updates_the_initial_tc_phase_when_class_changes_to_another_tc_class(): void
    {
        $service = app(BtsOrientationService::class);
        [$inscription] = $this->makeOrientationFixture();
        $service->ensureInitialPhase($inscription);

        $newTcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $inscription->filiere_id,
            'niveau_etude_id' => $inscription->niveau_id,
            'annee_universitaire_id' => $inscription->annee_universitaire_id,
        ]);

        $inscription->update(['classe_id' => $newTcClasse->id]);

        $updated = $service->syncAfterClassChange($inscription, $newTcClasse);

        $this->assertCount(1, $updated->phases);
        $this->assertSame(ESBTPInscriptionPhase::TYPE_TRONC_COMMUN, $updated->phases->first()->type_phase);
        $this->assertSame($newTcClasse->id, $updated->phases->first()->classe_id);
        $this->assertTrue((bool) $updated->phases->first()->is_active);
    }

    /** @test */
    public function it_blocks_regular_class_change_after_an_active_specialisation(): void
    {
        $service = app(BtsOrientationService::class);
        [$inscription, $targetClasse] = $this->makeOrientationFixture();
        $specialised = $service->orient($inscription->fresh(['filiere', 'classe.orientationTargets', 'phases']), $targetClasse->id);

        $otherFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false]);
        $otherClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $otherFiliere->id,
            'niveau_etude_id' => $specialised->niveau_id,
            'annee_universitaire_id' => $specialised->annee_universitaire_id,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $service->syncAfterClassChange($specialised, $otherClasse);
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
