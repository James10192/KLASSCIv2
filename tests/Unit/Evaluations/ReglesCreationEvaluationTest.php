<?php

namespace Tests\Unit\Evaluations;

use App\Domain\Evaluations\ReglesCreationEvaluation;
use App\Models\ESBTPEvaluation;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Règles de création partagées entre l'écran et l'API CLI. Fonctions pures :
 * aucune base requise.
 */
class ReglesCreationEvaluationTest extends TestCase
{
    public function test_une_duree_saisie_positive_est_retenue(): void
    {
        $debut = Carbon::parse('2026-04-30 08:00');
        $fin = Carbon::parse('2026-04-30 10:00');

        $this->assertSame(90, ReglesCreationEvaluation::dureeMinutes($debut, $fin, 90));
        $this->assertSame(90, ReglesCreationEvaluation::dureeMinutes($debut, $fin, '90'));
    }

    public function test_sans_saisie_la_duree_vient_des_heures(): void
    {
        $debut = Carbon::parse('2026-04-30 08:00');
        $fin = Carbon::parse('2026-04-30 10:30');

        $this->assertSame(150, ReglesCreationEvaluation::dureeMinutes($debut, $fin, null));
        $this->assertSame(150, ReglesCreationEvaluation::dureeMinutes($debut, $fin, ''));
    }

    public function test_une_saisie_nulle_ou_negative_retombe_sur_les_heures(): void
    {
        $debut = Carbon::parse('2026-04-30 08:00');
        $fin = Carbon::parse('2026-04-30 09:00');

        $this->assertSame(60, ReglesCreationEvaluation::dureeMinutes($debut, $fin, 0));
        $this->assertSame(60, ReglesCreationEvaluation::dureeMinutes($debut, $fin, -15));
    }

    public function test_une_periode_numerique_devient_un_semestre(): void
    {
        $this->assertSame('semestre1', ReglesCreationEvaluation::normaliserPeriode('1'));
        $this->assertSame('semestre3', ReglesCreationEvaluation::normaliserPeriode(3));
        $this->assertSame('semestre2', ReglesCreationEvaluation::normaliserPeriode('semestre2'));
    }

    public function test_le_statut_brouillon_reste_brouillon(): void
    {
        $evaluation = new ESBTPEvaluation;

        $this->assertSame(ESBTPEvaluation::STATUS_DRAFT, ReglesCreationEvaluation::statut(false, $evaluation));
    }

    public function test_les_bornes_du_bareme_et_du_coefficient_sont_celles_du_modele(): void
    {
        $regles = ReglesCreationEvaluation::champs(null);

        $this->assertSame(
            'required|numeric|min:'.ESBTPEvaluation::BAREME_MIN.'|max:'.ESBTPEvaluation::BAREME_MAX,
            $regles['bareme']
        );
        $this->assertSame(
            'required|numeric|min:'.ESBTPEvaluation::COEFFICIENT_MIN.'|max:'.ESBTPEvaluation::COEFFICIENT_MAX,
            $regles['coefficient']
        );
    }
}
