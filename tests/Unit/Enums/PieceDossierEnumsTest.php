<?php

namespace Tests\Unit\Enums;

use App\Enums\AppartenancePieceDossier;
use App\Enums\EcheancePieceDossier;
use App\Enums\FormePieceDossier;
use PHPUnit\Framework\TestCase;

/**
 * Les trois énumérations du catalogue des pièces, sans base de données.
 *
 * Ce qui est vérifié ici n'est pas cosmétique : c'est le contrat que la
 * validation, l'écran et les lots suivants tiennent pour acquis.
 */
class PieceDossierEnumsTest extends TestCase
{
    /**
     * La distinction qui décide de ce qu'une école redemande chaque rentrée.
     *
     * Elle n'est pas d'agrément : une pièce qui appartient à l'étudiant est
     * déposée une fois et consommée année après année ; une pièce qui
     * appartient à l'inscription est redonnée. Se tromper de camp, c'est soit
     * réclamer trois fois un extrait de naissance, soit ne jamais redemander un
     * certificat périmé.
     */
    public function test_l_appartenance_a_deux_valeurs_et_ne_bouge_pas(): void
    {
        $this->assertSame(['etudiant', 'inscription'], AppartenancePieceDossier::values());
    }

    public function test_seule_la_piece_de_l_etudiant_se_reporte(): void
    {
        $this->assertTrue(AppartenancePieceDossier::ETUDIANT->seReporte());
        $this->assertFalse(AppartenancePieceDossier::INSCRIPTION->seReporte());
    }

    public function test_les_formes_attendues_couvrent_original_copie_et_indifferent(): void
    {
        $this->assertSame(['original', 'copie', 'indifferent'], FormePieceDossier::values());
    }

    public function test_les_echeances_distinguent_l_inscription_de_la_fin_d_annee(): void
    {
        $this->assertSame(['inscription', 'avant_fin_annee'], EcheancePieceDossier::values());
    }

    public function test_les_options_de_selecteur_sont_indexees_par_valeur(): void
    {
        $options = FormePieceDossier::selectOptions();

        $this->assertArrayHasKey('copie', $options);
        $this->assertSame('Copie', $options['copie']);
        $this->assertCount(3, $options);

        $this->assertSame(
            ["À fournir à l'inscription", "À fournir avant la fin de l'année"],
            array_values(EcheancePieceDossier::selectOptions())
        );
    }

    /**
     * La lecture tolérante sert aux réglages saisis à la main : une école qui
     * écrit « Copie » avec une majuscule ne doit pas casser l'écran.
     */
    public function test_la_lecture_toleante_accepte_la_casse_et_les_espaces(): void
    {
        $this->assertSame(FormePieceDossier::ORIGINAL, FormePieceDossier::tryFromLibre('  ORIGINAL '));
        $this->assertSame(FormePieceDossier::COPIE, FormePieceDossier::tryFromLibre('Copie'));
        $this->assertSame(
            EcheancePieceDossier::AVANT_FIN_ANNEE,
            EcheancePieceDossier::tryFromLibre('Avant_Fin_Annee')
        );
    }

    /**
     * Et elle rend null sur l'inconnu, plutôt que de choisir à la place de
     * l'appelant : lui seul sait s'il lit un réglage d'école ou une donnée
     * corrompue, et ce qu'il faut faire dans chaque cas.
     */
    public function test_la_lecture_toleante_rend_null_sur_une_valeur_inconnue(): void
    {
        $this->assertNull(FormePieceDossier::tryFromLibre('photocopie'));
        $this->assertNull(FormePieceDossier::tryFromLibre(''));
        $this->assertNull(FormePieceDossier::tryFromLibre(null));
        $this->assertNull(EcheancePieceDossier::tryFromLibre('plus tard'));
    }
}
