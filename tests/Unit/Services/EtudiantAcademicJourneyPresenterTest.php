<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Services\EtudiantAcademicJourneyPresenter;
use Illuminate\Support\Collection;
use Tests\TestCase;

class EtudiantAcademicJourneyPresenterTest extends TestCase
{
    private int $nextId = 1;

    public function test_it_orders_bts_to_lmd_mixed_journey_and_marks_bridge(): void
    {
        $btsFiliere = $this->model(ESBTPFiliere::class, ['name' => 'Batiment']);
        $lmdFiliere = $this->model(ESBTPFiliere::class, ['name' => 'Genie civil']);

        $bts1 = $this->makeClasse('BTS 1 Batiment', 'BTS', 1, $btsFiliere);
        $bts2 = $this->makeClasse('BTS 2 Batiment', 'BTS', 2, $btsFiliere);
        $l3 = $this->makeClasse('Licence 3 Genie civil', 'Licence', 3, $lmdFiliere, $this->makeParcours($lmdFiliere));

        $annee1 = $this->annee('2023-2024', '2023-09-01');
        $annee2 = $this->annee('2024-2025', '2024-09-01');
        $annee3 = $this->annee('2025-2026', '2025-09-01');

        $inscriptions = collect([
            $this->inscription($bts1, $btsFiliere, $annee1, '2023-09-10'),
            $this->inscription($bts2, $btsFiliere, $annee2, '2024-09-10'),
            $this->inscription($l3, $lmdFiliere, $annee3, '2025-09-10'),
        ]);

        $lmdBulletins = collect([
            $this->lmdBulletin($l3, $annee3, 5, 13, 25, 30),
            $this->lmdBulletin($l3, $annee3, 6, 15, 30, 30, 4, 38),
        ]);

        $journey = (new EtudiantAcademicJourneyPresenter())
            ->presentFromCollections($inscriptions, lmdBulletins: $lmdBulletins);

        $items = $journey['items'];

        $this->assertSame(['BTS', 'BTS', 'LMD'], $items->pluck('system')->all());
        $this->assertTrue($journey['summary']['has_mixed_path']);
        $this->assertSame('bridge_bts_lmd', $items[2]['transition']['type']);
        $this->assertSame('55 / 60 CECT', $items[2]['metrics']['credits_label']);
        $this->assertSame('4/38', $items[2]['metrics']['rang_label']);
    }

    public function test_it_flags_multiple_inscriptions_in_the_same_academic_year(): void
    {
        $filiere = $this->model(ESBTPFiliere::class, ['name' => 'Travaux publics']);
        $annee = $this->annee('2025-2026', '2025-09-01');
        $classeA = $this->makeClasse('BTS 1 TP A', 'BTS', 1, $filiere);
        $classeB = $this->makeClasse('BTS 1 TP B', 'BTS', 1, $filiere);

        $journey = (new EtudiantAcademicJourneyPresenter())->presentFromCollections(collect([
            $this->inscription($classeA, $filiere, $annee, '2025-09-01'),
            $this->inscription($classeB, $filiere, $annee, '2025-09-02'),
        ]));

        $this->assertCount(1, $journey['summary']['duplicate_years']);
        $this->assertSame('duplicate_same_year', $journey['items'][1]['transition']['type']);
    }

    /**
     * Cas mesuré sur esbtp-abidjan : la fiche moyennait les
     * deux bulletins bruts (9,46 et 10,43 → 9,95) et prenait le rang du
     * semestre 2 (34), là où le bulletin et les résultats disent 10,02 et 33.
     */
    public function test_une_annee_bts_affiche_la_moyenne_et_le_rang_annuels_officiels(): void
    {
        [$inscription, $bulletins, $cle] = $this->anneeBtsAvecDeuxBulletins();

        $journey = (new EtudiantAcademicJourneyPresenter())->presentFromCollections(
            collect([$inscription]),
            btsBulletins: $bulletins,
            bilansBts: collect([$cle => [
                'moyenne' => 10.02,
                'complet' => true,
                'provisoire' => false,
                'semestres' => ['semestre1' => 9.46, 'semestre2' => 10.30],
                'rang' => 33,
                'effectif' => 47,
            ]])
        );

        $metrics = $journey['items'][0]['metrics'];
        $this->assertSame('10,02 / 20', $metrics['moyenne_label']);
        $this->assertSame('33/47', $metrics['rang_label']);
        $this->assertSame(10.30, $metrics['bilan']['semestres']['semestre2']);
    }

    public function test_une_annee_incomplete_prend_le_rang_calcule_du_semestre(): void
    {
        [$inscription, $bulletins, $cle] = $this->anneeBtsAvecDeuxBulletins();

        $journey = (new EtudiantAcademicJourneyPresenter())->presentFromCollections(
            collect([$inscription]),
            btsBulletins: $bulletins->take(1),
            bilansBts: collect([$cle => [
                'moyenne' => 9.46,
                'complet' => false,
                'provisoire' => true,
                'semestres' => ['semestre1' => 9.46, 'semestre2' => null],
                'rang' => 30,
                'effectif' => 47,
            ]])
        );

        $metrics = $journey['items'][0]['metrics'];
        $this->assertSame('9,46 / 20', $metrics['moyenne_label']);
        // Le rang calculé (30), pas celui stocké sur le bulletin (28).
        $this->assertSame('30/47', $metrics['rang_label']);
        $this->assertSame('Moyenne provisoire (un semestre)', $metrics['source']);
    }

    public function test_un_calcul_en_echec_n_affiche_pas_la_moyenne_brute(): void
    {
        [$inscription, $bulletins, $cle] = $this->anneeBtsAvecDeuxBulletins();

        $journey = (new EtudiantAcademicJourneyPresenter())->presentFromCollections(
            collect([$inscription]),
            btsBulletins: $bulletins,
            bilansBts: collect([$cle => ['indisponible' => true]])
        );

        $metrics = $journey['items'][0]['metrics'];
        $this->assertNull($metrics['moyenne']);
        $this->assertNull($metrics['rang_label']);
        $this->assertTrue($metrics['bilan_indisponible']);
    }

    private function anneeBtsAvecDeuxBulletins(): array
    {
        $filiere = $this->model(ESBTPFiliere::class, ['name' => 'Travaux publics']);
        $annee = $this->annee('2025-2026', '2025-09-01');
        $classe = $this->makeClasse('1BTS GTP D', 'BTS', 1, $filiere);
        $bulletin = fn (string $periode, float $moyenne, int $rang) => $this->model(ESBTPBulletin::class, [
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => $periode,
            'moyenne_generale' => $moyenne,
            'rang' => $rang,
            'effectif_classe' => 47,
        ]);

        return [
            $this->inscription($classe, $filiere, $annee, '2025-10-02'),
            collect([$bulletin('semestre1', 9.46, 28), $bulletin('semestre2', 10.43, 34)]),
            $classe->id . ':' . $annee->id,
        ];
    }

    private function makeClasse(string $name, string $niveauType, int $year, ESBTPFiliere $filiere, ?ESBTPLMDParcours $parcours = null): ESBTPClasse
    {
        $niveau = $this->model(ESBTPNiveauEtude::class, [
            'name' => $niveauType === 'BTS' ? 'BTS ' . $year : 'Licence ' . $year,
            'type' => $niveauType,
            'year' => $year,
        ]);

        return $this->model(ESBTPClasse::class, [
            'name' => $name,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'parcours_id' => $parcours?->id,
            'systeme_academique' => $niveauType === 'BTS' ? 'BTS' : 'LMD',
        ], [
            'filiere' => $filiere,
            'niveauEtude' => $niveau,
            'niveau' => $niveau,
            'parcours' => $parcours,
        ]);
    }

    private function makeParcours(ESBTPFiliere $filiere): ESBTPLMDParcours
    {
        $domaine = $this->model(ESBTPLMDDomaine::class, ['name' => 'Sciences et Technologies', 'code' => 'ST']);
        $mention = $this->model(ESBTPLMDMention::class, ['name' => 'Genie Civil', 'code' => 'GC', 'domaine_id' => $domaine->id], [
            'domaine' => $domaine,
        ]);

        return $this->model(ESBTPLMDParcours::class, [
            'name' => 'Batiment et urbanisme',
            'code' => 'BU',
            'mention_id' => $mention->id,
            'filiere_id' => $filiere->id,
        ], [
            'mention' => $mention,
            'filiere' => $filiere,
        ]);
    }

    private function annee(string $name, string $startDate): ESBTPAnneeUniversitaire
    {
        return $this->model(ESBTPAnneeUniversitaire::class, [
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => ((int) substr($startDate, 0, 4) + 1) . '-07-31',
        ]);
    }

    private function inscription(ESBTPClasse $classe, ESBTPFiliere $filiere, ESBTPAnneeUniversitaire $annee, string $date): ESBTPInscription
    {
        $inscription = $this->model(ESBTPInscription::class, [
            'classe_id' => $classe->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $annee->id,
            'date_inscription' => $date,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'affectation_status' => 'affecté',
            'is_sous_reserve' => false,
        ], [
            'classe' => $classe,
            'filiere' => $filiere,
            'niveauEtude' => $classe->niveauEtude,
            'niveau' => $classe->niveauEtude,
            'anneeUniversitaire' => $annee,
            'phases' => new Collection(),
        ]);

        $inscription->setAttribute('bts_journey_ui', null);

        return $inscription;
    }

    private function lmdBulletin(ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, int $semestre, float $moyenne, int $credits, int $creditsTotaux, ?int $rang = null, ?int $effectif = null): ESBTPLMDBulletin
    {
        return $this->model(ESBTPLMDBulletin::class, [
            'classe_id' => $classe->id,
            'parcours_id' => $classe->parcours_id,
            'annee_universitaire_id' => $annee->id,
            'semestre' => $semestre,
            'moyenne_generale' => $moyenne,
            'credits_capitalises' => $credits,
            'credits_totaux' => $creditsTotaux,
            'rang' => $rang,
            'effectif' => $effectif,
        ]);
    }

    private function model(string $class, array $attributes = [], array $relations = [])
    {
        $model = new $class($attributes);
        $model->setAttribute('id', $this->nextId++);
        $model->exists = true;

        foreach ($relations as $name => $relation) {
            if ($relation !== null) {
                $model->setRelation($name, $relation);
            }
        }

        return $model;
    }
}
