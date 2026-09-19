<?php

namespace Tests\Unit\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\CoverageTeacherContactResolver;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Le repli sur les noms de professeurs saisis au bulletin.
 *
 * Sans base : la regle qui compte est le rapprochement des copies. Chaque
 * bulletin porte SA copie des noms ; l'ecran sait les propager a la classe,
 * mais rien ne l'impose. Deux copies qui se contredisent, c'est la meme
 * situation que deux enseignants au planning — on ne tranche pas au hasard.
 *
 * Contexte : sur ESBTP Abidjan, la classe 2BTS GBAT B n'a AUCUNE planification,
 * donc ses onze matieres annoncaient « Enseignant a confirmer » alors que
 * l'ecole avait saisi les professeurs sur le bulletin, juste a cote.
 */
class CoverageTeacherContactResolverTest extends TestCase
{
    /** @param list<string|null> $bulletins */
    private function noms(array $bulletins, array $matieresVoulues): array
    {
        $m = new ReflectionMethod(CoverageTeacherContactResolver::class, 'nomsParMatiere');
        $m->setAccessible(true);

        return $m->invoke(new CoverageTeacherContactResolver(), new Collection($bulletins), $matieresVoulues);
    }

    public function test_un_nom_partage_par_tous_les_bulletins_est_retenu(): void
    {
        $noms = $this->noms([
            json_encode([7 => 'M. KOUAME']),
            json_encode([7 => 'M. KOUAME']),
        ], [7]);

        $this->assertSame([7 => 'M. KOUAME'], $noms);
    }

    public function test_deux_noms_qui_se_contredisent_ne_designent_personne(): void
    {
        $noms = $this->noms([
            json_encode([7 => 'M. KOUAME']),
            json_encode([7 => 'Mme DIALLO']),
        ], [7]);

        $this->assertSame([], $noms, 'Designer l un des deux ferait relancer quelqu un qui n y peut rien.');
    }

    public function test_une_matiere_hors_de_la_liste_demandee_est_ignoree(): void
    {
        // La matiere 9 a deja un contact venu du planning : on ne l ecrase pas.
        $noms = $this->noms([json_encode([7 => 'M. KOUAME', 9 => 'M. AUTRE'])], [7]);

        $this->assertSame([7 => 'M. KOUAME'], $noms);
    }

    public function test_un_nom_vide_ou_blanc_ne_compte_pas(): void
    {
        $this->assertSame([], $this->noms([json_encode([7 => '   '])], [7]));
        $this->assertSame([], $this->noms([json_encode([7 => ''])], [7]));
        $this->assertSame([], $this->noms([json_encode([7 => null])], [7]));
    }

    public function test_un_bulletin_vide_ou_illisible_n_empeche_pas_les_autres(): void
    {
        $noms = $this->noms([
            null,
            'ceci n est pas du json',
            json_encode([7 => 'M. KOUAME']),
        ], [7]);

        $this->assertSame([7 => 'M. KOUAME'], $noms);
    }

    public function test_les_espaces_autour_du_nom_ne_creent_pas_un_desaccord(): void
    {
        $noms = $this->noms([
            json_encode([7 => 'M. KOUAME']),
            json_encode([7 => '  M. KOUAME  ']),
        ], [7]);

        $this->assertSame([7 => 'M. KOUAME'], $noms);
    }

    public function test_la_cle_matiere_rendue_en_chaine_par_json_reste_un_entier(): void
    {
        // json_decode rend des cles numeriques en chaines : sans le cast, la
        // comparaison avec la liste des matieres attendues echouerait en silence.
        $noms = $this->noms([json_encode(['7' => 'M. KOUAME'])], [7]);

        $this->assertSame([7 => 'M. KOUAME'], $noms);
        $this->assertSame([7], array_keys($noms));
    }
}
