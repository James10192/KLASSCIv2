<?php

namespace Tests\Feature\Students;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPResultat;
use App\Services\EtudiantAcademicJourneyPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtudiantAcademicJourneyPresenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_presents_flat_bts_lmd_and_result_rows_from_the_production_path(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();

        [$btsBulletinClasse, $btsBulletinAnnee] = $this->makeClasse('BTS', 1, '2023-2024');
        $btsResultatClasse = $this->makeClasseForAnnee('BTS', 2, $btsBulletinAnnee);
        [$lmdClasse, $lmdAnnee] = $this->makeClasse('Licence', 3, '2025-2026');

        $this->inscrire($etudiant, $btsBulletinClasse, $btsBulletinAnnee, '2023-09-10');
        $this->inscrire($etudiant, $btsResultatClasse, $btsBulletinAnnee, '2023-09-11');
        $this->inscrire($etudiant, $lmdClasse, $lmdAnnee, '2025-09-10');

        ESBTPBulletin::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $btsBulletinClasse->id,
            'annee_universitaire_id' => $btsBulletinAnnee->id,
            'periode' => 'semestre1',
            'moyenne_generale' => 12.5,
        ]);

        ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $btsResultatClasse->id,
            'matiere_id' => ESBTPMatiere::factory()->create()->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $btsBulletinAnnee->id,
            'moyenne' => 14,
            'coefficient' => 2,
        ]);

        ESBTPBulletin::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $btsBulletinClasse->id,
            'annee_universitaire_id' => $lmdAnnee->id,
            'periode' => 'semestre1',
            'moyenne_generale' => 19.5,
        ]);

        ESBTPLMDBulletin::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $lmdClasse->id,
            'annee_universitaire_id' => $lmdAnnee->id,
            'semestre' => 5,
            'moyenne_generale' => 13,
            'credits_capitalises' => 30,
            'credits_totaux' => 30,
        ]);

        $journey = app(EtudiantAcademicJourneyPresenter::class)->present($etudiant->fresh());

        $this->assertSame(['Bulletins BTS', 'Résultats saisis', 'Bulletins LMD'], $journey['items']->pluck('metrics.source')->all());
        $this->assertSame([12.5, 14.0, 13.0], $journey['items']->map(fn (array $item) => (float) $item['metrics']['moyenne'])->all());
    }

    /**
     * @return array{0: ESBTPClasse, 1: ESBTPAnneeUniversitaire}
     */
    private function makeClasse(string $type, int $year, string $anneeName): array
    {
        [$startYear, $endYear] = array_map('intval', explode('-', $anneeName));
        $annee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => $anneeName,
            'start_date' => "{$startYear}-09-01",
            'end_date' => "{$endYear}-07-31",
        ]);
        return [
            $this->makeClasseForAnnee($type, $year, $annee),
            $annee,
        ];
    }

    private function makeClasseForAnnee(string $type, int $year, ESBTPAnneeUniversitaire $annee): ESBTPClasse
    {
        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create([
            'name' => "{$type} {$year}",
            'type' => $type,
            'year' => $year,
        ]);

        return ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => $type === 'BTS' ? 'BTS' : 'LMD',
        ]);
    }

    private function inscrire(ESBTPEtudiant $etudiant, ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, string $date): void
    {
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'date_inscription' => $date,
        ]);
    }
}
