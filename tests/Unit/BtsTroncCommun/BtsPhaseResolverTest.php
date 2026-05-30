<?php

namespace Tests\Unit\BtsTroncCommun;

use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BtsPhaseResolverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_resolves_phase_based_semesters(): void
    {
        [$inscription, $tcClasse, $specClasse] = $this->makePhaseBasedInscription();

        $resolver = app(BtsPhaseResolver::class);

        $s1 = $resolver->resolveSemesterPhase($inscription->fresh(['phases.classe.filiere']), 1);
        $s2 = $resolver->resolveSemesterPhase($inscription->fresh(['phases.classe.filiere']), 2);

        $this->assertSame($tcClasse->id, $s1['classe_id']);
        $this->assertSame($specClasse->id, $s2['classe_id']);
        $this->assertSame('phase_based', $resolver->buildJourney($inscription->fresh(['phases.classe.filiere']))['source_model']);
    }

    /** @test */
    public function it_keeps_legacy_dual_inscription_compatible(): void
    {
        [$origine, $specialisation] = $this->makeLegacyJourney();

        $resolver = app(BtsPhaseResolver::class);
        $journey = $resolver->buildJourney($specialisation->fresh(['inscriptionOrigine.classe.filiere']));

        $this->assertSame('legacy_dual_inscription', $journey['source_model']);
        $this->assertCount(2, $journey['timeline']);
        $this->assertSame('specialisation', $journey['current_phase']['type_phase']);
        $this->assertSame($origine->classe_id, $journey['timeline'][0]['classe_id']);
    }

    private function makePhaseBasedInscription(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
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

    private function makeLegacyJourney(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
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

        return [$origine, $specialisation];
    }
}
