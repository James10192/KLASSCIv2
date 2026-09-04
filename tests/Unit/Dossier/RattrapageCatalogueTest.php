<?php

namespace Tests\Unit\Dossier;

use App\Services\Dossier\MaterialisationPiecesService as Materialisation;
use PHPUnit\Framework\TestCase;

/**
 * Que devient un dossier deja constitue quand le catalogue change ensuite ?
 *
 * C'est la question qui fera la difference a la deuxieme rentree, et la reponse
 * appartient a l'ecole : elle se regle par instance. Ces tests fixent ce que
 * chaque reglage veut dire.
 */
class RattrapageCatalogueTest extends TestCase
{
    private const ANNEE_COURANTE = 12;
    private const ANNEE_CLOSE = 9;

    /**
     * Defaut : une piece ajoutee en octobre, apres la rentree, apparait sur les
     * dossiers ouverts — sinon la liste transmise au ministere est fausse et le
     * secretariat reprend deux mille dossiers a la main.
     */
    public function test_par_defaut_les_dossiers_de_l_annee_en_cours_sont_rattrapes(): void
    {
        $this->assertSame(
            Materialisation::RATTRAPAGE_ANNEE_COURANTE,
            Materialisation::RATTRAPAGE_DEFAUT
        );

        $this->assertTrue(Materialisation::rattrapageAutorise(
            Materialisation::RATTRAPAGE_DEFAUT,
            self::ANNEE_COURANTE,
            self::ANNEE_COURANTE
        ));
    }

    /**
     * Une annee close etait complete au regard des exigences de SON annee.
     * La rouvrir en « incomplet » reecrirait l'histoire.
     */
    public function test_les_annees_closes_ne_bougent_pas_par_defaut(): void
    {
        $this->assertFalse(Materialisation::rattrapageAutorise(
            Materialisation::RATTRAPAGE_ANNEE_COURANTE,
            self::ANNEE_CLOSE,
            self::ANNEE_COURANTE
        ));
    }

    public function test_le_mode_aucun_fige_tous_les_dossiers_existants(): void
    {
        $this->assertFalse(Materialisation::rattrapageAutorise(
            Materialisation::RATTRAPAGE_AUCUN,
            self::ANNEE_COURANTE,
            self::ANNEE_COURANTE
        ));
    }

    public function test_le_mode_toutes_rattrape_meme_les_annees_closes(): void
    {
        $this->assertTrue(Materialisation::rattrapageAutorise(
            Materialisation::RATTRAPAGE_TOUTES,
            self::ANNEE_CLOSE,
            self::ANNEE_COURANTE
        ));
    }

    /**
     * Pas d'annee courante determinee : on ne touche a rien plutot que de
     * deviner. Un dossier modifie par erreur coute plus qu'un dossier en retard.
     */
    public function test_sans_annee_courante_connue_on_ne_touche_a_rien(): void
    {
        $this->assertFalse(Materialisation::rattrapageAutorise(
            Materialisation::RATTRAPAGE_ANNEE_COURANTE,
            self::ANNEE_COURANTE,
            null
        ));

        $this->assertFalse(Materialisation::rattrapageAutorise(
            Materialisation::RATTRAPAGE_ANNEE_COURANTE,
            null,
            self::ANNEE_COURANTE
        ));
    }

    public function test_un_mode_inconnu_ne_rattrape_rien(): void
    {
        $this->assertFalse(Materialisation::rattrapageAutorise(
            'mode_qui_n_existe_pas',
            self::ANNEE_COURANTE,
            self::ANNEE_COURANTE
        ));
    }

    public function test_les_trois_modes_sont_les_seuls_acceptes(): void
    {
        $this->assertSame(
            ['aucun', 'annee_courante', 'toutes'],
            Materialisation::modesRattrapage()
        );
    }
}
