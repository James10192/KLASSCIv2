<?php

namespace Tests\Feature\Bulletin;

use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BtsClassCohortCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_oriented_student_counts_in_tc_on_s1_and_specialty_on_s2(): void
    {
        $ctx = $this->makeOrientedContext();
        $counter = app(BtsClassCohortCounter::class);

        $this->assertSame(1, $counter->countPourPeriode($ctx['tcClasse']->id, $ctx['annee']->id, 'semestre1'));
        $this->assertSame(0, $counter->countPourPeriode($ctx['specClasse']->id, $ctx['annee']->id, 'semestre1'));
        $this->assertSame(0, $counter->countPourPeriode($ctx['tcClasse']->id, $ctx['annee']->id, 'semestre2'));
        $this->assertSame(1, $counter->countPourPeriode($ctx['specClasse']->id, $ctx['annee']->id, 'semestre2'));
    }

    /**
     * @return array{annee: ESBTPAnneeUniversitaire, tcClasse: ESBTPClasse, specClasse: ESBTPClasse}
     */
    private function makeOrientedContext(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $specClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $specFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
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

        return [
            'annee' => $annee,
            'tcClasse' => $tcClasse,
            'specClasse' => $specClasse,
        ];
    }
}
