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

    /** @test */
    public function it_corrects_an_active_specialisation_from_its_original_tc_phase(): void
    {
        $service = app(BtsOrientationService::class);
        [$inscription, $firstTargetClasse] = $this->makeOrientationFixture();
        $firstOrientation = $service->orient(
            $inscription->fresh(['filiere', 'classe.orientationTargets', 'phases']),
            $firstTargetClasse->id
        );

        $secondFiliere = ESBTPFiliere::factory()->create([
            'parent_id' => $inscription->filiere_id,
            'is_tronc_commun' => false,
        ]);
        $secondTargetClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $secondFiliere->id,
            'niveau_etude_id' => $inscription->niveau_id,
            'annee_universitaire_id' => $inscription->annee_universitaire_id,
        ]);
        $sourceClasseId = $inscription->classe_id;
        ESBTPClasseOrientationTarget::create([
            'source_classe_id' => $sourceClasseId,
            'target_classe_id' => $secondTargetClasse->id,
            'semestre_activation' => 2,
            'is_active' => true,
        ]);

        $corrected = $service->correctOrientation(
            $firstOrientation,
            $secondTargetClasse->id,
            'Correction de la spécialité après validation administrative.'
        );

        $activePhases = $corrected->phases->where('is_active', true);

        $this->assertSame($secondTargetClasse->id, $corrected->classe_id);
        $this->assertSame($secondFiliere->id, $corrected->filiere_id);
        $this->assertCount(3, $corrected->phases);
        $this->assertCount(1, $activePhases);
        $this->assertSame($secondTargetClasse->id, $activePhases->first()->classe_id);
        $this->assertSame(
            'Correction de la spécialité après validation administrative.',
            $activePhases->first()->correction_reason
        );
        $this->assertFalse((bool) $corrected->phases->firstWhere('classe_id', $firstTargetClasse->id)->is_active);
    }

    /** @test */
    public function it_rejects_a_noop_specialisation_correction(): void
    {
        $service = app(BtsOrientationService::class);
        [$inscription, $targetClasse] = $this->makeOrientationFixture();
        $oriented = $service->orient(
            $inscription->fresh(['filiere', 'classe.orientationTargets', 'phases']),
            $targetClasse->id
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('déjà à la spécialisation active');

        $service->correctOrientation($oriented, $targetClasse->id, 'Motif de correction.');
    }

    /** @test */
    public function it_reports_an_active_phase_without_a_primary_class_pointer(): void
    {
        $service = app(BtsOrientationService::class);
        [$inscription, $targetClasse] = $this->makeOrientationFixture();
        $oriented = $service->orient(
            $inscription->fresh(['filiere', 'classe.orientationTargets', 'phases']),
            $targetClasse->id
        );

        $oriented->update(['classe_id' => null]);

        $result = $service->syncSingleInscription($oriented->fresh(['filiere', 'classe.filiere', 'phases.classe.filiere']));

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('sans classe principale', $result['message']);
        $this->assertNull($oriented->fresh()->classe_id);
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
