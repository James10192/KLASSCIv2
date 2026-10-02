<?php

namespace Tests\Unit\Reinscription;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPRegleAcademique;
use App\Services\BulletinService;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use App\Services\Reinscription\MoyennesAnnuellesDuBulletin;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * La reinscription decide sur la moyenne annuelle PONDEREE du bulletin, pas
 * sur une moyenne simple des matieres. Sans base : les lectures (bulletins
 * enregistres, snapshots) sont remplacees par les valeurs que le bulletin
 * imprime ; ce qui est verifie est la combinaison et la decision qui en sort.
 *
 * Cas mesure : FESBTP25-0433 (esbtp-abidjan, BTS1, poids S1 x1 + S2 x2) —
 * bulletin S1 10,97, S2 9,89, annuelle imprimee 10,25.
 */
class MoyennesAnnuellesDuBulletinTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_la_moyenne_annuelle_est_celle_du_bulletin_pondere_1_2(): void
    {
        $resultat = $this->service(['semestre1' => 10.97, 'semestre2' => 9.89])->pour([$this->inscription(1)]);

        $this->assertEqualsWithDelta(10.25, $resultat[1]['moyenne'], 0.005);
        $this->assertTrue($this->regle()->peutPasser($resultat[1]['moyenne']), '10,25 >= 10 : passage, comme le bulletin.');
    }

    public function test_un_s2_fort_ne_rattrape_pas_un_s1_faible_au_dela_de_son_poids(): void
    {
        // Moyenne simple des deux semestres : 8,83. Ponderee 1/2 : 9,30.
        // Le seuil de passage a 10 la refuse dans les deux cas, mais c'est
        // 9,30 que la reinscription doit afficher et comparer.
        $resultat = $this->service(['semestre1' => 7.41, 'semestre2' => 10.24])->pour([$this->inscription(2)]);

        $this->assertEqualsWithDelta(9.30, $resultat[2]['moyenne'], 0.005);
        $this->assertFalse($this->regle()->peutPasser($resultat[2]['moyenne']));
    }

    public function test_un_semestre_sans_moyenne_ne_rend_pas_de_moyenne_annuelle(): void
    {
        $resultat = $this->service(['semestre1' => 12.0, 'semestre2' => null])->pour([$this->inscription(3)]);

        $this->assertNull(
            $resultat[3]['moyenne'],
            'Le bulletin n\'imprime pas d\'annuelle sans les deux semestres : la reinscription ne doit pas l\'inventer.'
        );
        $this->assertSame(12.0, $resultat[3]['semestre1']);
    }

    public function test_une_classe_lmd_n_est_pas_traitee(): void
    {
        $resultat = $this->service(['semestre1' => 15.0, 'semestre2' => 15.0])
            ->pour([$this->inscription(4, 'LMD')]);

        $this->assertSame([], $resultat);
    }

    private function regle(): ESBTPRegleAcademique
    {
        return new ESBTPRegleAcademique(['moyenne_passage' => 10, 'moyenne_rattrapage' => 7, 'max_matieres_rattrapage' => 2]);
    }

    private function inscription(int $id, string $systeme = 'BTS'): ESBTPInscription
    {
        $classe = new ESBTPClasse(['systeme_academique' => $systeme]);
        $classe->id = 10;

        $inscription = new ESBTPInscription();
        $inscription->id = $id;
        $inscription->etudiant_id = 100 + $id;
        $inscription->classe_id = 10;
        $inscription->annee_universitaire_id = 5;
        $inscription->setRelation('classe', $classe);

        return $inscription;
    }

    /**
     * @param  array{semestre1: float|null, semestre2: float|null}  $semestres
     */
    private function service(array $semestres): MoyennesAnnuellesDuBulletin
    {
        $bulletins = Mockery::mock(BulletinService::class)->makePartial();
        $bulletins->shouldReceive('getSemesterWeights')->andReturn(['semester1' => 1.0, 'semester2' => 2.0, 'year' => 1]);

        $cartes = Mockery::mock(BtsAnnualClassMapResolver::class);
        $cartes->shouldReceive('resolveForInscription')
            ->andReturn(['inscription_id' => null, 'source_model' => 'phase_based', 'semestre1_classe_id' => 10, 'semestre2_classe_id' => 10]);

        $snapshots = Mockery::mock(BtsCurrentResultSnapshotService::class);

        return new class($bulletins, $snapshots, $cartes, $semestres) extends MoyennesAnnuellesDuBulletin
        {
            public function __construct($bulletins, $snapshots, $cartes, private array $semestres)
            {
                parent::__construct($bulletins, $snapshots, $cartes);
            }

            protected function chargerLaCarte(EloquentCollection $inscriptions): void
            {
            }

            protected function moyennesEnregistrees(Collection $inscriptions, array $classesDuSemestre, int $anneeId): array
            {
                $moyennes = [];
                foreach ($inscriptions as $inscription) {
                    $moyennes[$inscription->id] = array_filter($this->semestres, fn ($v) => $v !== null);
                }

                return $moyennes;
            }

            protected function moyennesCourantes(Collection $inscriptions, array $classesDuSemestre, array $enregistrees, int $anneeId): array
            {
                return [];
            }
        };
    }
}
