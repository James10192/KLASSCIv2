<?php

namespace Tests\Feature\Snapshot;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Parité class-map : après c2, BtsCurrentResultSnapshotService délègue intégralement
 * la résolution du class-map annuel à BtsAnnualClassMapResolver. Ce test vérifie que
 * le snapshot annuel expose EXACTEMENT le même class-map que le résolveur partagé,
 * pour les 3 scénarios load-bearing (sans inscription, phases orienté, legacy dual).
 *
 * BTS uniquement (LMD intouché).
 */
class BtsCurrentResultSnapshotClassMapTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function snapshot_class_map_matches_resolver_when_no_inscription(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $etudiant = ESBTPEtudiant::factory()->create();

        $service = app(BtsCurrentResultSnapshotService::class);
        $resolver = app(BtsAnnualClassMapResolver::class);

        $snapshot = $service->getAnnualSnapshot($etudiant->id, 4242, $annee->id);
        $expected = $resolver->resolve($etudiant->id, 4242, $annee->id);

        $this->assertSame($expected, $snapshot['class_map']);
        $this->assertNull($snapshot['class_map']['inscription_id']);
        $this->assertSame(4242, $snapshot['class_map']['semestre1_classe_id']);
        $this->assertSame(4242, $snapshot['class_map']['semestre2_classe_id']);
    }

    /** @test */
    public function snapshot_class_map_matches_resolver_for_phase_based_oriented_student(): void
    {
        [$inscription, $tcClasse, $specClasse] = $this->makePhaseBasedInscription();

        $service = app(BtsCurrentResultSnapshotService::class);
        $resolver = app(BtsAnnualClassMapResolver::class);

        $snapshot = $service->getAnnualSnapshot(
            $inscription->etudiant_id,
            $specClasse->id,
            $inscription->annee_universitaire_id
        );
        $expected = $resolver->resolve(
            $inscription->etudiant_id,
            $specClasse->id,
            $inscription->annee_universitaire_id
        );

        $this->assertSame($expected, $snapshot['class_map']);
        $this->assertSame($inscription->id, $snapshot['class_map']['inscription_id']);
        $this->assertSame('phase_based', $snapshot['class_map']['source_model']);
        $this->assertSame($tcClasse->id, $snapshot['class_map']['semestre1_classe_id']);
        $this->assertSame($specClasse->id, $snapshot['class_map']['semestre2_classe_id']);
    }

    /** @test */
    public function snapshot_class_map_matches_resolver_for_legacy_dual_inscription(): void
    {
        [$specialisation, $tcClasse, $specClasse] = $this->makeLegacyJourney();

        $service = app(BtsCurrentResultSnapshotService::class);
        $resolver = app(BtsAnnualClassMapResolver::class);

        $snapshot = $service->getAnnualSnapshot(
            $specialisation->etudiant_id,
            $specClasse->id,
            $specialisation->annee_universitaire_id
        );
        $expected = $resolver->resolve(
            $specialisation->etudiant_id,
            $specClasse->id,
            $specialisation->annee_universitaire_id
        );

        $this->assertSame($expected, $snapshot['class_map']);
        $this->assertSame($specialisation->id, $snapshot['class_map']['inscription_id']);
        $this->assertSame('legacy_dual_inscription', $snapshot['class_map']['source_model']);
        $this->assertSame($tcClasse->id, $snapshot['class_map']['semestre1_classe_id']);
        $this->assertSame($specClasse->id, $snapshot['class_map']['semestre2_classe_id']);
    }

    /**
     * @return array{0: ESBTPAnneeUniversitaire, 1: ESBTPNiveauEtude, 2: ESBTPFiliere}
     */
    private function makeAcademicContext(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);

        return [$annee, $niveau, $tcFiliere];
    }

    /**
     * @return array{0: ESBTPInscription, 1: ESBTPClasse, 2: ESBTPClasse}
     */
    private function makePhaseBasedInscription(): array
    {
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tcClasse = ESBTPClasse::factory()->create(['filiere_id' => $tcFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $specClasse = ESBTPClasse::factory()->create(['filiere_id' => $specFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $specClasse->id,
            'filiere_id' => $specFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        return [$inscription, $tcClasse, $specClasse];
    }

    /**
     * @return array{0: ESBTPInscription, 1: ESBTPClasse, 2: ESBTPClasse}
     */
    private function makeLegacyJourney(): array
    {
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tcClasse = ESBTPClasse::factory()->create(['filiere_id' => $tcFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $specClasse = ESBTPClasse::factory()->create(['filiere_id' => $specFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $etudiant = ESBTPEtudiant::factory()->create();

        $origine = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $tcClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        $specialisation = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specClasse->id,
            'annee_universitaire_id' => $annee->id,
            'type_changement' => 'specialisation',
            'inscription_origine_id' => $origine->id,
        ]);

        return [$specialisation, $tcClasse, $specClasse];
    }
}
