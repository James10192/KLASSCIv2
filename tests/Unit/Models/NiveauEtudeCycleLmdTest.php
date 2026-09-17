<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPClasse;
use App\Models\ESBTPNiveauEtude;
use App\Rules\AnneeDuCycleLmd;
use Tests\TestCase;

/**
 * L'annee d'un niveau LMD se compte depuis la Licence (Master 1 = annee 4).
 * Ces tests fixent la convention sans toucher a la base (application demarree
 * seulement : les modeles en ont besoin pour etre instancies).
 */
class NiveauEtudeCycleLmdTest extends TestCase
{
    public function test_les_semestres_suivent_l_annee_continue(): void
    {
        $this->assertSame([7, 8], $this->classeEnAnnee(4)->getSemestresLMD(), 'Master 1');
        $this->assertSame([9, 10], $this->classeEnAnnee(5)->getSemestresLMD(), 'Master 2');
        $this->assertSame([5, 6], $this->classeEnAnnee(3)->getSemestresLMD(), 'Licence 3');
        $this->assertSame([7, 8], (new ESBTPNiveauEtude(['year' => 4]))->semestres());
    }

    public function test_les_cycles_et_les_annees_nomment_les_memes_grades(): void
    {
        $this->assertSame(ESBTPNiveauEtude::CYCLES_LMD, array_keys(ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD));
    }

    public function test_les_listes_de_semestres_vont_jusqu_au_master_2(): void
    {
        $this->assertSame(range(1, 10), ESBTPNiveauEtude::semestresLmd());
    }

    public function test_le_cycle_se_deduit_de_l_annee(): void
    {
        $this->assertSame('Licence', ESBTPNiveauEtude::cycleLmdPourAnnee(3));
        $this->assertSame('Master', ESBTPNiveauEtude::cycleLmdPourAnnee(4));
        $this->assertSame('Doctorat', ESBTPNiveauEtude::cycleLmdPourAnnee(6));
        $this->assertNull(ESBTPNiveauEtude::cycleLmdPourAnnee(9));
    }

    public function test_un_master_en_annee_1_est_refuse(): void
    {
        $this->assertNotNull($this->echecPour(['type' => 'Master'], 'niveau', 1));
        $this->assertNull($this->echecPour(['type' => 'Master'], 'niveau', 4));
    }

    public function test_la_regle_lit_le_type_sur_la_meme_ligne_d_un_tableau(): void
    {
        $donnees = ['niveaux' => [['type' => 'Licence'], ['type' => 'Master']]];

        $this->assertNull($this->echecPour($donnees, 'niveaux.0.year', 2));
        $this->assertNotNull($this->echecPour($donnees, 'niveaux.1.year', 2));
    }

    public function test_hors_lmd_la_regle_ne_dit_rien(): void
    {
        $this->assertNull($this->echecPour(['type' => 'BTS'], 'niveau', 7));
        $this->assertNull($this->echecPour([], 'niveau', 7));
    }

    private function echecPour(array $donnees, string $attribut, int $valeur): ?string
    {
        $regle = (new AnneeDuCycleLmd())->setData($donnees);

        return $regle->passes($attribut, $valeur) ? null : $regle->message();
    }

    private function classeEnAnnee(int $annee): ESBTPClasse
    {
        $classe = new ESBTPClasse();
        $classe->setRelation('niveau', new ESBTPNiveauEtude(['type' => 'Master', 'year' => $annee]));

        return $classe;
    }
}
