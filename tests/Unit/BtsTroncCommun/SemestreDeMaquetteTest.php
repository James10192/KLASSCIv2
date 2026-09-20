<?php

namespace Tests\Unit\BtsTroncCommun;

use App\Domain\BtsTroncCommun\SemestreDeMaquette;
use PHPUnit\Framework\TestCase;

/**
 * La regle qui a manque a l'endpoint de chargement de maquette.
 *
 * Aucune base : la regle est pure, et c'est tout l'interet de l'avoir sortie
 * du controleur. Le scenario qui a casse ESBTP Abidjan est nomme tel quel.
 */
class SemestreDeMaquetteTest extends TestCase
{
    public function test_les_deux_se_saisit_par_mot_par_vide_ou_par_null(): void
    {
        $this->assertNull(SemestreDeMaquette::depuisLaSaisie('les_deux'));
        $this->assertNull(SemestreDeMaquette::depuisLaSaisie(null));
        $this->assertNull(SemestreDeMaquette::depuisLaSaisie(''));
    }

    public function test_un_semestre_se_saisit_en_nombre_ou_en_chaine(): void
    {
        $this->assertSame(1, SemestreDeMaquette::depuisLaSaisie(1));
        $this->assertSame(2, SemestreDeMaquette::depuisLaSaisie('2'));
    }

    public function test_une_ligne_vide_ne_fait_pas_conflit(): void
    {
        // Premier chargement : il n'y a rien a contredire.
        $this->assertFalse(SemestreDeMaquette::estUnConflit(null, false, 1));
        $this->assertFalse(SemestreDeMaquette::estUnConflit(null, false, null));
    }

    /**
     * Le trou par lequel le defaut fondateur est passe.
     *
     * Un chargement sans `valider` ecrit le semestre et laisse la ligne non
     * validee. La premiere version de cette regle ne regardait QUE les lignes
     * validees : deux chargements de suite, S1 puis S2, basculaient donc en
     * silence toute matiere commune aux deux — precisement ce qu'elle devait
     * empecher. Une valeur inerte reste une valeur.
     */
    public function test_une_ligne_non_validee_qui_porte_deja_un_semestre_fait_conflit(): void
    {
        $this->assertTrue(SemestreDeMaquette::estUnConflit(2, false, 1));
        $this->assertTrue(SemestreDeMaquette::estUnConflit(1, false, 2));
        $this->assertTrue(SemestreDeMaquette::estUnConflit(2, false, null));
    }

    public function test_recharger_le_meme_semestre_sur_une_ligne_non_validee_ne_fait_pas_conflit(): void
    {
        $this->assertFalse(SemestreDeMaquette::estUnConflit(2, false, 2));
    }

    public function test_recharger_le_meme_semestre_ne_fait_pas_conflit(): void
    {
        $this->assertFalse(SemestreDeMaquette::estUnConflit(2, true, 2));
        $this->assertFalse(SemestreDeMaquette::estUnConflit(null, true, null));
    }

    /**
     * Le defaut fondateur : chez ESBTP Abidjan, la maquette de Batiment
     * 2e annee portait dix matieres figees au semestre 2 par un chargement.
     * Charger ensuite le semestre 1 les faisait basculer a 1 sans un mot, et
     * elles disparaissaient du bulletin du second semestre.
     */
    public function test_charger_l_autre_semestre_sur_une_ligne_validee_est_un_conflit(): void
    {
        $this->assertTrue(SemestreDeMaquette::estUnConflit(2, true, 1));
        $this->assertTrue(SemestreDeMaquette::estUnConflit(1, true, 2));
    }

    public function test_restreindre_une_matiere_declaree_aux_deux_est_un_conflit(): void
    {
        // « Elle est aux deux » puis « elle est au semestre 1 » : c'est peut
        // etre une correction, peut etre un chargement partiel. On ne devine pas.
        $this->assertTrue(SemestreDeMaquette::estUnConflit(null, true, 1));
    }

    public function test_les_deux_couvre_les_deux_semestres(): void
    {
        $this->assertSame([1, 2], SemestreDeMaquette::semestresCouverts(null));
        $this->assertSame([1], SemestreDeMaquette::semestresCouverts(1));
        $this->assertSame([2], SemestreDeMaquette::semestresCouverts(2));
    }

    public function test_les_libelles_sont_lisibles_dans_un_rapport(): void
    {
        $this->assertSame('semestre 1', SemestreDeMaquette::libelle(1));
        $this->assertSame('semestre 2', SemestreDeMaquette::libelle(2));
        $this->assertSame('les deux semestres', SemestreDeMaquette::libelle(null));
    }

    /**
     * Le cas qui a fait diverger l'ecran du bulletin, et le seul qui compte.
     *
     * Une ligne NON validee vaut « les deux », quel que soit le semestre
     * qu'elle porte. `ChargementDeMaquette` pose un semestre sans valider :
     * lire `semestre` sans consulter `semestre_renseigne` retire du bulletin
     * des matieres que le bulletin garde.
     *
     * L'ecran de maquette l'avait remplace par une garde PAR COUPLE — juste
     * tant qu'aucune ligne n'etait validee, fausse des qu'une seule l'etait.
     */
    public function test_une_ligne_non_validee_vaut_les_deux_semestres(): void
    {
        $this->assertNull(SemestreDeMaquette::declarationEffective(2, false));
        $this->assertNull(SemestreDeMaquette::declarationEffective(1, false));
        $this->assertNull(SemestreDeMaquette::declarationEffective(null, false));
    }

    public function test_une_ligne_validee_declare_son_semestre(): void
    {
        $this->assertSame(1, SemestreDeMaquette::declarationEffective(1, true));
        $this->assertSame(2, SemestreDeMaquette::declarationEffective(2, true));
        // Validee ET sans semestre : c'est « les deux », dit explicitement.
        $this->assertNull(SemestreDeMaquette::declarationEffective(null, true));
    }

    public function test_non_validee_au_semestre_2_reste_prevue_au_semestre_1(): void
    {
        // Le bout par lequel l'ecran se trompait : il annonçait cette matiere
        // hors du semestre 1, le bulletin l'y laissait.
        $declare = SemestreDeMaquette::declarationEffective(2, false);

        $this->assertTrue(SemestreDeMaquette::estPrevueAu($declare, 1));
        $this->assertTrue(SemestreDeMaquette::estPrevueAu($declare, 2));
    }
}
