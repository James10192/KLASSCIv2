<?php

namespace Tests\Unit\Enums;

use App\Enums\EcheancePieceDossier;
use App\Enums\EtatPieceDossier;
use App\Enums\FormePieceDossier;
use PHPUnit\Framework\TestCase;

/**
 * Les trois énumérations du suivi des pièces, sans base de données.
 *
 * Ce qui est vérifié ici n'est pas cosmétique : c'est le contrat que la
 * validation, l'écran et les lots suivants tiennent pour acquis.
 */
class PieceDossierEnumsTest extends TestCase
{
    public function test_les_cinq_etats_existent_et_ne_bougent_pas(): void
    {
        // Cinq états, pas un booléen : voir le commentaire de l'énumération.
        // Si cette liste change, tout ce qui lit `etat` en base doit être relu.
        $this->assertSame(
            ['attendue', 'deposee', 'validee', 'refusee', 'non_applicable'],
            EtatPieceDossier::values()
        );
    }

    public function test_seul_le_refus_exige_un_motif(): void
    {
        $this->assertTrue(EtatPieceDossier::REFUSEE->exigeUnMotif());

        foreach ([
            EtatPieceDossier::ATTENDUE,
            EtatPieceDossier::DEPOSEE,
            EtatPieceDossier::VALIDEE,
            EtatPieceDossier::NON_APPLICABLE,
        ] as $etat) {
            $this->assertFalse($etat->exigeUnMotif(), $etat->value . ' ne doit pas exiger de motif.');
        }
    }

    /**
     * Une pièce déposée n'est PAS soldée : quelqu'un a remis quelque chose,
     * personne n'a encore dit que c'était la bonne pièce.
     */
    public function test_seules_validee_et_sans_objet_soldent_la_piece(): void
    {
        $this->assertTrue(EtatPieceDossier::VALIDEE->estSoldee());
        $this->assertTrue(EtatPieceDossier::NON_APPLICABLE->estSoldee());

        $this->assertFalse(EtatPieceDossier::ATTENDUE->estSoldee());
        $this->assertFalse(EtatPieceDossier::DEPOSEE->estSoldee());
        $this->assertFalse(EtatPieceDossier::REFUSEE->estSoldee());
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
