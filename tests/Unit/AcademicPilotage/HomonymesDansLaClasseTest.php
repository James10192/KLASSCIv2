<?php

namespace Tests\Unit\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Le rapprochement qui explique une note « manquante » qui ne l'est pas.
 *
 * Cas fondateur, ESBTP Abidjan, classe 2BTS GBAT B : le bandeau annoncait
 * « 1 note manquante » sur Pathologie, et la direction affirmait que tout avait
 * ete saisi. Les deux disaient vrai. La classe portait deux inscriptions
 * actives pour ce qui est tres probablement la meme personne :
 *
 *   1443  KOUASSI AFFOUE GRACE          FESBTP23-0322  — note 12,00
 *   1444  KOUASSI AFFOUE GRACE RUCHAMA  FESBTP24-0022  — rien
 *
 * Le service est instancie SANS constructeur : ces deux regles ne dependent
 * d'aucune de ses dependances, et les faire venir n'apporterait qu'une base de
 * donnees dont le test n'a pas besoin.
 */
class HomonymesDansLaClasseTest extends TestCase
{
    private function service(): AcademicNoteCoverageService
    {
        return (new ReflectionClass(AcademicNoteCoverageService::class))->newInstanceWithoutConstructor();
    }

    private function appeler(string $methode, array $args): mixed
    {
        $m = new ReflectionMethod(AcademicNoteCoverageService::class, $methode);
        $m->setAccessible(true);

        return $m->invokeArgs($this->service(), $args);
    }

    /** @param list<array{id:int,name:string,matricule:string}> $eleves */
    private function homonymes(array $eleves, int $idCible): array
    {
        $index = new Collection($eleves);
        $cible = $index->firstWhere('id', $idCible);

        return $this->appeler('homonymesDansLaClasse', [$cible, $index]);
    }

    public function test_un_prenom_supplementaire_designe_un_doublon_probable(): void
    {
        $trouves = $this->homonymes([
            ['id' => 1443, 'name' => 'KOUASSI AFFOUE GRACE', 'matricule' => 'FESBTP23-0322'],
            ['id' => 1444, 'name' => 'KOUASSI AFFOUE GRACE RUCHÂMA', 'matricule' => 'FESBTP24-0022'],
        ], 1444);

        $this->assertCount(1, $trouves);
        $this->assertSame(1443, $trouves[0]['id']);
        $this->assertSame('FESBTP23-0322', $trouves[0]['matricule']);
    }

    public function test_le_rapprochement_va_dans_les_deux_sens(): void
    {
        $eleves = [
            ['id' => 1443, 'name' => 'KOUASSI AFFOUE GRACE', 'matricule' => 'A'],
            ['id' => 1444, 'name' => 'KOUASSI AFFOUE GRACE RUCHÂMA', 'matricule' => 'B'],
        ];

        $this->assertSame(1444, $this->homonymes($eleves, 1443)[0]['id']);
        $this->assertSame(1443, $this->homonymes($eleves, 1444)[0]['id']);
    }

    public function test_l_accent_et_la_casse_ne_separent_pas_deux_homonymes(): void
    {
        $trouves = $this->homonymes([
            ['id' => 1, 'name' => 'KOUASSI RUCHÂMA', 'matricule' => 'A'],
            ['id' => 2, 'name' => 'kouassi ruchama', 'matricule' => 'B'],
        ], 1);

        $this->assertCount(1, $trouves);
    }

    public function test_un_mot_simplement_rallonge_n_est_pas_un_homonyme(): void
    {
        // « GRACE » et « GRACES » ne partagent pas de frontiere de mot : ce
        // sont deux noms differents, pas un prenom en plus.
        $trouves = $this->homonymes([
            ['id' => 1, 'name' => 'KOUASSI GRACE', 'matricule' => 'A'],
            ['id' => 2, 'name' => 'KOUASSI GRACES', 'matricule' => 'B'],
        ], 1);

        $this->assertSame([], $trouves);
    }

    public function test_deux_eleves_sans_rapport_ne_sont_pas_rapproches(): void
    {
        $trouves = $this->homonymes([
            ['id' => 1, 'name' => 'KOUASSI GRACE', 'matricule' => 'A'],
            ['id' => 2, 'name' => 'TRAORE MOUSSA', 'matricule' => 'B'],
        ], 1);

        $this->assertSame([], $trouves);
    }

    public function test_un_eleve_n_est_jamais_son_propre_homonyme(): void
    {
        $trouves = $this->homonymes([
            ['id' => 1, 'name' => 'KOUASSI GRACE', 'matricule' => 'A'],
        ], 1);

        $this->assertSame([], $trouves);
    }

    public function test_un_nom_vide_ne_rapproche_personne(): void
    {
        $trouves = $this->homonymes([
            ['id' => 1, 'name' => '   ', 'matricule' => 'A'],
            ['id' => 2, 'name' => 'KOUASSI GRACE', 'matricule' => 'B'],
        ], 1);

        $this->assertSame([], $trouves);
    }

    public function test_le_nom_comparable_retire_accents_casse_et_espaces_multiples(): void
    {
        $this->assertSame(
            'KOUASSI AFFOUE GRACE RUCHAMA',
            $this->appeler('nomComparable', ['  kouassi   affoué  grace RUCHÂMA '])
        );
    }
}
