<?php

namespace Tests\Unit\Support;

use App\Support\CoefficientsAffiches;
use PHPUnit\Framework\TestCase;

class CoefficientsAffichesTest extends TestCase
{
    /**
     * Le defaut d'origine : la carte de synthese sommait la colonne brute de la
     * ligne de resultat, qui vaut 1 sur toute ligne nee avant le correctif du
     * coefficient. Elle affichait donc le NOMBRE de matieres, a quelques
     * centimetres d'un tableau qui sommait, lui, les coefficients configures.
     */
    public function test_le_coefficient_configure_prime_sur_la_colonne_brute(): void
    {
        $matieres = [
            ['matiere_coefficient' => 3.0, 'total_coefficients' => 1.0],
            ['matiere_coefficient' => 4.0, 'total_coefficients' => 1.0],
        ];

        $this->assertSame(7.0, CoefficientsAffiches::somme($matieres));
        $this->assertNotSame(
            2.0,
            CoefficientsAffiches::somme($matieres),
            'Sommer la colonne brute revient a compter les matieres.'
        );
    }

    public function test_la_colonne_brute_ne_sert_que_de_repli(): void
    {
        $this->assertSame(2.0, CoefficientsAffiches::pourUneMatiere(['total_coefficients' => 2.0]));
        $this->assertSame(0.0, CoefficientsAffiches::pourUneMatiere([]));
    }

    /**
     * Le defaut qui survivait au premier correctif : sur l'onglet annuel, le
     * tableau rend un bloc par semestre et la carte sommait un jeu reste scope
     * au semestre primaire. Elle annoncait le total d'un semestre au-dessus d'un
     * tableau qui en affichait deux.
     */
    public function test_sur_l_onglet_annuel_la_carte_somme_les_deux_semestres(): void
    {
        $blocs = [
            ['subjects' => [['matiere_coefficient' => 10.0], ['matiere_coefficient' => 8.0]]],
            ['subjects' => [['matiere_coefficient' => 12.0], ['matiere_coefficient' => 8.0]]],
        ];
        // Le jeu a plat, lui, ne porte que le semestre primaire.
        $aPlat = [['matiere_coefficient' => 10.0], ['matiere_coefficient' => 8.0]];

        $this->assertSame(38.0, CoefficientsAffiches::sommeAffichee($blocs, $aPlat));
        $this->assertNotSame(
            18.0,
            CoefficientsAffiches::sommeAffichee($blocs, $aPlat),
            "Sommer le jeu a plat n'annonce qu'un semestre au-dessus d'un tableau qui en rend deux."
        );
    }

    public function test_hors_onglet_annuel_la_carte_somme_le_jeu_unique(): void
    {
        $aPlat = [['matiere_coefficient' => 3.0], ['matiere_coefficient' => 4.0]];

        $this->assertSame(7.0, CoefficientsAffiches::sommeAffichee([], $aPlat));
    }

    /**
     * Un coefficient configure a zero est une decision — un etudiant dispense —
     * et non une absence de configuration. `?:` le ramenait a 1.
     */
    public function test_un_coefficient_configure_a_zero_reste_zero(): void
    {
        $this->assertSame(
            0.0,
            CoefficientsAffiches::pourUneMatiere(['matiere_coefficient' => 0.0, 'total_coefficients' => 5.0]),
            'Zero configure doit primer sur le repli, pas ouvrir la porte au repli.'
        );
    }
}
