<?php

declare(strict_types=1);

namespace Tests\Unit\Services\LMD;

use App\Services\LMD\CompositionDuBulletin;
use PHPUnit\Framework\TestCase;

/**
 * Verrouille ce qu'un bulletin retient d'une maquette, et ce qu'il oublie.
 *
 * Le calcul ajoutait et mettait à jour les unités de la maquette, il n'en
 * enlevait jamais : une unité retirée gardait sa ligne, imprimée sur le
 * bulletin avec sa moyenne et ses crédits, alors que le total des crédits et la
 * moyenne générale se recalculent sur la composition relue et ne la comptaient
 * pas.
 *
 * Aucune base ici : la décision est isolée de son exécution, précisément parce
 * que c'est elle qui peut détruire des résultats.
 */
class CompositionDuBulletinTest extends TestCase
{
    public function test_une_unite_retiree_de_la_maquette_est_elaguee(): void
    {
        // Le bulletin porte trois lignes, la maquette n'en retient plus que deux.
        $this->assertSame(
            [30],
            CompositionDuBulletin::idsAElaguer([10, 20], [10, 20, 30]),
        );
    }

    public function test_une_composition_inchangee_n_elague_rien(): void
    {
        $this->assertSame([], CompositionDuBulletin::idsAElaguer([10, 20], [10, 20]));
    }

    public function test_une_unite_ajoutee_n_elague_rien(): void
    {
        // La ligne vient d'être créée : elle est retenue sans être encore
        // présente au moment où l'on a relevé l'existant.
        $this->assertSame([], CompositionDuBulletin::idsAElaguer([10, 20, 30], [10, 20]));
    }

    public function test_une_composition_vide_n_elague_rien(): void
    {
        // Le cas qui compte. La maquette peut être vide passagèrement : le
        // nettoyage avant réimport supprime les liens parcours-unité PUIS met
        // les unités à la corbeille, et désactiver les matières d'une unité vide
        // sa liste d'éléments. Vider le bulletin dans ces fenêtres effacerait
        // des résultats — note de seconde session comprise — pour un état qui
        // n'a duré qu'un instant.
        $this->assertSame([], CompositionDuBulletin::idsAElaguer([], [10, 20, 30]));
    }

    public function test_un_bulletin_vide_et_une_maquette_vide_ne_produisent_rien(): void
    {
        $this->assertSame([], CompositionDuBulletin::idsAElaguer([], []));
    }

    public function test_l_elagage_rend_des_identifiants_reindexes(): void
    {
        // `array_diff` conserve les clés d'origine : sans réindexation, le
        // tableau rendu serait associatif et `whereIn` recevrait des trous.
        $retirees = CompositionDuBulletin::idsAElaguer([20], [10, 20, 30]);

        $this->assertSame([0, 1], array_keys($retirees));
        $this->assertSame([10, 30], $retirees);
    }
}
