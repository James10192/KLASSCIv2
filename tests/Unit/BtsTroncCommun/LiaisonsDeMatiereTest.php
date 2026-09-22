<?php

namespace Tests\Unit\BtsTroncCommun;

use App\Domain\BtsTroncCommun\LiaisonsDeMatiere;
use PHPUnit\Framework\TestCase;

/**
 * Le diff de la maquette d'une matiere, sans base et sans HTTP.
 *
 * `appliquerLEnsembleVoulu()` ne fait que lire l'existant et executer ce que
 * `planifier()` decide. C'est donc `planifier()` qui porte les regles : ne rien
 * reposer de ce qui est deja la, lire la liste vide comme « tout retirer », et
 * ne jamais iterer une chaine.
 *
 * @see \App\Domain\BtsTroncCommun\LiaisonsDeMatiere::planifier()
 */
class LiaisonsDeMatiereTest extends TestCase
{
    private function couple(int|string $filiereId, int|string $niveauId): array
    {
        return ['filiere_id' => $filiereId, 'niveau_id' => $niveauId];
    }

    /**
     * Le couple garde (2,1) n'est ni retire ni repose. Le reposer serait sans
     * effet sur une matiere BTS, mais `poser()` refuse une ECUE : retirer un
     * couple d'une ECUE qui en porte deux leverait sur le second.
     */
    public function test_pose_ce_qui_manque_retire_ce_qui_n_est_plus_voulu_et_ne_touche_pas_au_reste(): void
    {
        $plan = (new LiaisonsDeMatiere())->planifier(
            [$this->couple(1, 1), $this->couple(2, 1)],
            [$this->couple(2, 1), $this->couple(3, 1)],
        );

        $this->assertSame([[3, 1]], $plan['a_poser']);
        $this->assertSame([[1, 1]], $plan['a_retirer']);
        $this->assertSame(2, $plan['voulues']);
    }

    /**
     * Le formulaire envoie des chaines ; la base rend des entiers. Les deux
     * doivent designer le meme couple, sinon il serait retire puis repose.
     */
    public function test_un_couple_demande_deux_fois_ne_compte_qu_une_fois(): void
    {
        $plan = (new LiaisonsDeMatiere())->planifier(
            [$this->couple(4, 2)],
            [$this->couple(1, 1), $this->couple('1', '1'), $this->couple(4, 2), $this->couple('4', '2')],
        );

        $this->assertSame(2, $plan['voulues']);
        $this->assertSame([[1, 1]], $plan['a_poser']);
        $this->assertSame([], $plan['a_retirer']);
    }

    public function test_la_liste_vide_est_une_instruction_elle_retire_tout(): void
    {
        $plan = (new LiaisonsDeMatiere())->planifier(
            [$this->couple(1, 1), $this->couple(2, 3)],
            [],
        );

        $this->assertSame([[1, 1], [2, 3]], $plan['a_retirer']);
        $this->assertSame([], $plan['a_poser']);
        $this->assertSame(0, $plan['voulues']);
    }

    /**
     * LE REFUS D'UNE CHAINE NE DOIT PAS DEPENDRE D'`error_reporting`.
     *
     * Les avertissements sont coupes ici expres : c'est le reglage sous lequel
     * un `foreach` sur `'x'` ne dit rien, ne voit « aucun couple voulu », et
     * rend l'existant entier a retirer. Seul le type `array` leve alors.
     */
    public function test_une_chaine_leve_meme_quand_les_avertissements_sont_coupes(): void
    {
        $avant = error_reporting(E_ALL & ~E_WARNING);

        try {
            $this->expectException(\TypeError::class);
            (new LiaisonsDeMatiere())->planifier([$this->couple(1, 1)], 'x');
        } finally {
            error_reporting($avant);
        }
    }
}
