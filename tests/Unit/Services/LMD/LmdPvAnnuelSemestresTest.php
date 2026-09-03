<?php

namespace Tests\Unit\Services\LMD;

use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPNiveauEtude;
use App\Services\LMD\LmdPvAnnuelAssembler;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Les deux semestres couverts par un PV annuel.
 *
 * Le PV cherchait ses bulletins « au semestre 1 et au semestre 2 ». Les
 * semestres LMD etant numerotes en continu sur le cursus, ce PV sortait vide
 * des la Licence 2 (semestres 3 et 4) et en Licence 3 (semestres 5 et 6) —
 * soit deux niveaux sur trois pour une instance qui ouvre en Licence 1 a 3.
 *
 * Aucun acces base : on assemble les modeles en memoire. L'application est
 * amorcee seulement parce que l'amorcage des modeles Eloquent du projet passe
 * par des facades.
 */
class LmdPvAnnuelSemestresTest extends TestCase
{
    /** @dataProvider niveaux */
    public function test_les_semestres_suivent_le_niveau_de_la_classe(int $annee, array $attendu): void
    {
        $niveau = new ESBTPNiveauEtude();
        $niveau->year = $annee;

        $classe = new ESBTPClasse();
        $classe->setRelation('niveau', $niveau);

        $jury = new ESBTPLMDJury();
        $jury->setRelation('classe', $classe);

        self::assertSame($attendu, $this->resoudre($jury));
    }

    public static function niveaux(): array
    {
        return [
            'Licence 1' => [1, [1, 2]],
            'Licence 2' => [2, [3, 4]],
            'Licence 3' => [3, [5, 6]],
            'Master 1' => [4, [7, 8]],
        ];
    }

    public function test_sans_classe_le_semestre_du_jury_situe_l_annee(): void
    {
        $jury = new ESBTPLMDJury();
        $jury->setRelation('classe', null);
        $jury->semestre = 4;

        self::assertSame([3, 4], $this->resoudre($jury));
    }

    public function test_sans_classe_ni_semestre_les_bulletins_situent_l_annee(): void
    {
        $jury = new ESBTPLMDJury();
        $jury->setRelation('classe', null);

        $bulletins = collect([
            collect([(object) ['semestre' => 6], (object) ['semestre' => 5]]),
        ]);

        self::assertSame([5, 6], $this->resoudre($jury, $bulletins));
    }

    /** @return array{0:int, 1:int} */
    private function resoudre(ESBTPLMDJury $jury, ?Collection $bulletins = null): array
    {
        $methode = new ReflectionMethod(LmdPvAnnuelAssembler::class, 'semestresDeLAnnee');
        $methode->setAccessible(true);

        return $methode->invoke(new LmdPvAnnuelAssembler(), $jury, $bulletins ?? collect());
    }
}
