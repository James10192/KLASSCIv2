<?php

namespace Tests\Unit\LMD;

use App\Services\LMD\LectureMaquettes;
use PHPUnit\Framework\TestCase;

/**
 * Compteurs de la ligne d'unite, vus depuis une maquette de travail.
 *
 * Sans base : ces deux methodes sont pures, et c'est ce qui compte ici — un
 * compteur qui additionne les elements d'un AUTRE parcours ment a la personne
 * qui saisit, et le mensonge est silencieux.
 */
class LectureMaquettesTest extends TestCase
{
    /** @test */
    public function un_element_sans_parcours_est_vu_par_toutes_les_maquettes(): void
    {
        $this->assertTrue(LectureMaquettes::visibleDepuis([], 7));
        $this->assertTrue(LectureMaquettes::visibleDepuis([], null));
    }

    /** @test */
    public function un_element_reserve_n_est_vu_que_par_ses_maquettes(): void
    {
        $this->assertTrue(LectureMaquettes::visibleDepuis([7], 7));
        $this->assertFalse(LectureMaquettes::visibleDepuis([7], 9));
    }

    /** @test */
    public function sans_maquette_de_travail_tout_est_visible(): void
    {
        // « Toutes les maquettes » ne cache rien : c'est la vue d'ensemble.
        $this->assertTrue(LectureMaquettes::visibleDepuis([7], null));
    }

    /** @test */
    public function le_compteur_ne_compte_que_ce_que_la_maquette_voit(): void
    {
        $parcoursParEcue = [
            10 => [],       // partagé
            11 => [7],      // propre à la maquette 7
            12 => [9],      // propre à la maquette 9
            13 => [7, 9],   // dans les deux
        ];

        $vuDepuis7 = LectureMaquettes::compter($parcoursParEcue, [10, 11, 12, 13], 7);
        $this->assertSame(3, $vuDepuis7['visibles']);   // 10, 11, 13
        $this->assertSame(2, $vuDepuis7['reserves']);   // 11, 13

        $vuDepuis9 = LectureMaquettes::compter($parcoursParEcue, [10, 11, 12, 13], 9);
        $this->assertSame(3, $vuDepuis9['visibles']);   // 10, 12, 13
        $this->assertSame(2, $vuDepuis9['reserves']);   // 12, 13
    }

    /** @test */
    public function sans_maquette_de_travail_le_compteur_donne_le_total(): void
    {
        $parcoursParEcue = [10 => [], 11 => [7], 12 => [9]];

        $total = LectureMaquettes::compter($parcoursParEcue, [10, 11, 12], null);

        $this->assertSame(3, $total['visibles']);
        $this->assertSame(2, $total['reserves']);
    }

    /** @test */
    public function un_element_absent_de_la_table_est_traite_comme_partage(): void
    {
        // C'est l'etat des maquettes importees : elles n'ecrivent que la cle
        // etrangere, donc aucune ligne de pivot. Elles doivent rester visibles.
        $compte = LectureMaquettes::compter([], [10, 11], 7);

        $this->assertSame(2, $compte['visibles']);
        $this->assertSame(0, $compte['reserves']);
    }
}
