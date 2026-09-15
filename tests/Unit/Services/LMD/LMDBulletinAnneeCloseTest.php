<?php

declare(strict_types=1);

namespace Tests\Unit\Services\LMD;

use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Services\LMDBulletinService;
use PHPUnit\Framework\TestCase;

/**
 * Verrouille le refus de recalculer un bulletin sur une année refermée.
 *
 * Le pivot qui porte la maquette (`esbtp_lmd_parcours_ue`) n'a pas de colonne
 * d'année : la génération lit toujours la maquette d'aujourd'hui, même pour un
 * semestre de 2026-2027. Modifier une maquette en 2028 change donc ce qu'une
 * régénération produit pour une année déjà arrêtée — et le relevé de notes, lui,
 * relit les bulletins à chaque réémission.
 *
 * Aucune base ici : la décision est isolée de sa collecte, précisément pour
 * qu'elle se vérifie telle quelle.
 */
class LMDBulletinAnneeCloseTest extends TestCase
{
    public function test_une_annee_ouverte_ne_declenche_jamais_le_refus(): void
    {
        // Le cas de toutes les instances qui ne referment jamais leurs années :
        // le comportement doit être exactement celui d'avant cette garde.
        $this->assertFalse(LMDBulletinService::recalculInterdit(true, true, false));
        $this->assertFalse(LMDBulletinService::recalculInterdit(true, false, false));
    }

    public function test_le_premier_calcul_d_une_annee_close_reste_permis(): void
    {
        // Une école qui reprend un archivage en retard ne doit pas être bloquée :
        // un bulletin qui n'existe pas encore ne contredit aucune pièce délivrée.
        $this->assertFalse(LMDBulletinService::recalculInterdit(false, false, false));
    }

    public function test_le_recalcul_d_un_bulletin_existant_sur_annee_close_est_refuse(): void
    {
        $this->assertTrue(LMDBulletinService::recalculInterdit(false, true, false));
    }

    public function test_la_permission_de_contournement_leve_le_refus(): void
    {
        $this->assertFalse(LMDBulletinService::recalculInterdit(false, true, true));
    }

    public function test_le_refus_nomme_l_annee_la_sortie_et_repond_en_conflit(): void
    {
        $exception = AcademicPilotageException::closedAcademicYear(
            '2026-2027',
            7,
            LMDBulletinService::PERMISSION_ANNEE_CLOSE,
        );

        $this->assertSame(AcademicPilotageException::CLOSED_ACADEMIC_YEAR, $exception->errorCode);

        // 409 et non 422 : la demande est bien formée, c'est l'état de l'année
        // qui s'y oppose.
        $this->assertSame(409, $exception->statusCode);

        $this->assertStringContainsString('2026-2027', $exception->getMessage());

        // Un refus qui n'indique pas la sortie est un mur : le message doit
        // nommer les deux issues réelles.
        $this->assertStringContainsString('Rouvrez l’année', $exception->getMessage());
        $this->assertStringContainsString(
            LMDBulletinService::PERMISSION_ANNEE_CLOSE,
            $exception->getMessage(),
        );

        $this->assertSame(7, $exception->details['annee_universitaire_id']);
        $this->assertSame(
            LMDBulletinService::PERMISSION_ANNEE_CLOSE,
            $exception->details['permission'],
        );
    }

    public function test_la_permission_de_contournement_est_declaree_au_registre(): void
    {
        // Sans cette entrée, la permission n'existe dans aucune instance : la
        // garde deviendrait un refus sans issue, exactement ce qu'elle évite.
        $registre = require __DIR__.'/../../../../config/permissions.php';

        $this->assertArrayHasKey(
            LMDBulletinService::PERMISSION_ANNEE_CLOSE,
            $registre['permissions'],
        );
    }
}
