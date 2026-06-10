<?php

namespace Tests\Unit\BtsTroncCommun;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BtsAnnualClassMapResolverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_requested_classe_for_both_semesters_when_no_inscription(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $etudiant = ESBTPEtudiant::factory()->create();

        $resolver = app(BtsAnnualClassMapResolver::class);
        $map = $resolver->resolve($etudiant->id, 4242, $annee->id);

        $this->assertNull($map['inscription_id']);
        $this->assertSame('phase_based', $map['source_model']);
        $this->assertSame(4242, $map['semestre1_classe_id']);
        $this->assertSame(4242, $map['semestre2_classe_id']);
    }

    /** @test */
    public function it_resolves_pure_tronc_commun_to_same_classe_for_both_semesters(): void
    {
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        $tcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();

        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $tcClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 2,
            'is_active' => true,
        ]);

        $resolver = app(BtsAnnualClassMapResolver::class);
        $map = $resolver->resolve($etudiant->id, $tcClasse->id, $annee->id);

        $this->assertSame($inscription->id, $map['inscription_id']);
        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
        $this->assertSame($tcClasse->id, $map['semestre2_classe_id']);
    }

    /** @test */
    public function it_resolves_phase_based_oriented_student_to_tc_then_spe(): void
    {
        [$inscription, $tcClasse, $specClasse] = $this->makePhaseBasedInscription();

        $resolver = app(BtsAnnualClassMapResolver::class);
        $map = $resolver->resolve($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id);

        $this->assertSame($inscription->id, $map['inscription_id']);
        $this->assertSame('phase_based', $map['source_model']);
        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
        $this->assertSame($specClasse->id, $map['semestre2_classe_id']);
    }

    /** @test */
    public function it_resolves_legacy_dual_inscription_origine_then_spe(): void
    {
        [$origine, $specialisation, $tcClasse, $specClasse] = $this->makeLegacyJourney();

        $resolver = app(BtsAnnualClassMapResolver::class);
        $map = $resolver->resolve($specialisation->etudiant_id, $specClasse->id, $specialisation->annee_universitaire_id);

        // Le résolveur ordonne par classe_id-match en premier → la spécialisation gagne.
        $this->assertSame($specialisation->id, $map['inscription_id']);
        $this->assertSame('legacy_dual_inscription', $map['source_model']);
        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
        $this->assertSame($specClasse->id, $map['semestre2_classe_id']);
    }

    /** @test */
    public function it_picks_the_classe_id_matching_inscription_when_re_enrolled_same_year(): void
    {
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $classeA = ESBTPClasse::factory()->create([
            'filiere_id' => $specFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $classeB = ESBTPClasse::factory()->create([
            'filiere_id' => $specFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();

        // Deux inscriptions la même année — une sur classeA, une sur classeB.
        $inscA = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classeA->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $inscB = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classeB->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        $resolver = app(BtsAnnualClassMapResolver::class);

        // Demande classeB → c'est l'inscription B qui doit être résolue.
        $mapB = $resolver->resolve($etudiant->id, $classeB->id, $annee->id);
        $this->assertSame($inscB->id, $mapB['inscription_id']);

        // Demande classeA → c'est l'inscription A.
        $mapA = $resolver->resolve($etudiant->id, $classeA->id, $annee->id);
        $this->assertSame($inscA->id, $mapA['inscription_id']);
    }

    /** @test */
    public function it_propagates_source_model_from_journey(): void
    {
        [$inscription] = $this->makePhaseBasedInscription();

        $resolver = app(BtsAnnualClassMapResolver::class);
        $map = $resolver->resolve($inscription->etudiant_id, $inscription->classe_id, $inscription->annee_universitaire_id);

        $this->assertArrayHasKey('source_model', $map);
        $this->assertSame('phase_based', $map['source_model']);
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
     * @return array{0: ESBTPInscription, 1: ESBTPInscription, 2: ESBTPClasse, 3: ESBTPClasse}
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

        return [$origine, $specialisation, $tcClasse, $specClasse];
    }
}
