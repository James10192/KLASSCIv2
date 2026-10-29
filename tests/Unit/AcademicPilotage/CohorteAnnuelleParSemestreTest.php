<?php

namespace Tests\Unit\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Models\ESBTPEvaluation;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * La vue annuelle d'une classe de tronc commun.
 *
 * `BtsClassCohortCounter` rend, pour « annuel », la cohorte du SEUL semestre 2 —
 * et ce choix est le sien, deliberement : un bulletin annuel doit avoir un
 * proprietaire unique. Mais la couverture ne genere rien, elle compte. Sur une
 * classe de tronc commun, dont tous les etudiants passent en specialite au
 * semestre 2, cette cohorte est vide : l'ecran annoncait « aucun etudiant sur
 * cette periode » alors que tout le semestre 1 etait saisi.
 *
 * L'union des deux semestres repare ce cas — et en ouvre un autre, que ce test
 * ferme : un etudiant present au seul semestre 1 ne doit pas etre compte
 * manquant sur les evaluations du semestre 2.
 *
 * Herite de `Tests\TestCase` : `new ESBTPEvaluation()` declenche le boot du
 * modele, et `ESBTPEvaluation implements Auditable` y consulte la
 * configuration. Sans application demarree, cela ne passe que si un autre test
 * a deja boote ce modele dans le meme processus — une dependance a l'ordre
 * d'execution, pas une garantie.
 */
class CohorteAnnuelleParSemestreTest extends TestCase
{
    private function service(): AcademicNoteCoverageService
    {
        $service = (new ReflectionClass(AcademicNoteCoverageService::class))->newInstanceWithoutConstructor();

        $periods = (new ReflectionClass(AcademicNoteCoverageService::class))->getProperty('periods');
        $periods->setAccessible(true);
        $periods->setValue($service, new AcademicPeriodNormalizer());

        return $service;
    }

    private function evaluation(?string $periode): ESBTPEvaluation
    {
        $evaluation = new ESBTPEvaluation();
        $evaluation->periode = $periode;

        return $evaluation;
    }

    private function index(): Collection
    {
        return new Collection([
            10 => ['id' => 10, 'name' => 'PARTI APRES S1', 'matricule' => 'A'],
            20 => ['id' => 20, 'name' => 'TOUTE L ANNEE', 'matricule' => 'B'],
            30 => ['id' => 30, 'name' => 'ARRIVE EN S2', 'matricule' => 'C'],
        ]);
    }

    /** @return array<int, array<int, true>> */
    private function cohortes(): array
    {
        return [
            1 => [10 => true, 20 => true],
            2 => [20 => true, 30 => true],
        ];
    }

    private function attendus(?string $periode, array $cohortes): array
    {
        $m = new ReflectionMethod(AcademicNoteCoverageService::class, 'indexPourEvaluation');
        $m->setAccessible(true);

        return $m->invoke($this->service(), $this->evaluation($periode), $this->index(), $cohortes)
            ->keys()
            ->all();
    }

    public function test_une_evaluation_du_premier_semestre_n_attend_que_la_cohorte_du_premier(): void
    {
        $this->assertSame([10, 20], $this->attendus('semestre1', $this->cohortes()));
    }

    public function test_une_evaluation_du_second_semestre_n_attend_que_la_cohorte_du_second(): void
    {
        $this->assertSame([20, 30], $this->attendus('semestre2', $this->cohortes()));
    }

    /** Les ecritures de la base sont lues comme celles de l'ecran. */
    public function test_l_ecriture_de_la_periode_ne_change_rien(): void
    {
        $this->assertSame([20, 30], $this->attendus('S2', $this->cohortes()));
        $this->assertSame([20, 30], $this->attendus('Semestre 2', $this->cohortes()));
        $this->assertSame([10, 20], $this->attendus('1', $this->cohortes()));
    }

    /**
     * Hors vue annuelle BTS, aucune restriction : le tableau vide veut dire
     * « la cohorte ne bouge pas d'une evaluation a l'autre ».
     */
    public function test_sans_cohortes_par_semestre_l_index_complet_s_applique(): void
    {
        $this->assertSame([10, 20, 30], $this->attendus('semestre2', []));
    }

    /**
     * Une periode d'evaluation illisible, ou absente, ne doit rien faire
     * perdre : rendre une liste vide compterait toute la classe comme traitee,
     * et c'est le seul sens d'erreur qui fasse generer des bulletins a tort.
     */
    public function test_une_periode_illisible_ne_restreint_rien(): void
    {
        $this->assertSame([10, 20, 30], $this->attendus('trimestre 3', $this->cohortes()));
        $this->assertSame([10, 20, 30], $this->attendus(null, $this->cohortes()));
    }

    /** Une evaluation annuelle n'appartient a aucun semestre : tout le monde est attendu. */
    public function test_une_evaluation_annuelle_attend_l_union(): void
    {
        $this->assertSame([10, 20, 30], $this->attendus('annuel', $this->cohortes()));
    }
}
