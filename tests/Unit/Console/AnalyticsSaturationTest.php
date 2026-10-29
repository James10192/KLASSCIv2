<?php

namespace Tests\Unit\Console;

use App\Console\Commands\AnalyticsDiagnoseCommand;
use App\Console\Commands\EcheanciersRecompute;
use App\Domain\Analytics\Calibration\RiskSaturation;
use App\Domain\Analytics\Predictors\DefaultRiskPredictor;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * La saturation du classement de risque, et ce que le diagnostic en dit.
 *
 * Sans base : la regle est pure (RiskSaturation), et les recommandations du
 * diagnostic se lisent en appelant la methode privee sur un rapport fabrique.
 *
 * Le cas fondateur (presentation, 5 sept. 2026) : 60 % haut, 40 % moyen, 0 %
 * bas. Tout le monde est « a risque », et l'ancien test disait non sature parce
 * qu'il ne regardait que le haut.
 */
class AnalyticsSaturationTest extends TestCase
{
    /** @test */
    public function haut_et_moyen_reunis_saturent_meme_si_le_haut_seul_ne_sature_pas(): void
    {
        $eval = (new RiskSaturation(70.0, 90.0))->evaluer(['haut' => 60, 'moyen' => 40, 'bas' => 0], 100);

        $this->assertTrue($eval['is_saturated']);
        $this->assertSame(RiskSaturation::DECLENCHEUR_TOTAL, $eval['declencheur']);
        $this->assertSame(60.0, $eval['haut_pct']);
        $this->assertSame(100.0, $eval['total_pct']);
    }

    /** @test */
    public function le_haut_seul_suffit_a_saturer(): void
    {
        $eval = (new RiskSaturation(70.0, 90.0))->evaluer(['haut' => 7, 'moyen' => 0, 'bas' => 3], 10);

        $this->assertTrue($eval['is_saturated']);
        $this->assertSame(RiskSaturation::DECLENCHEUR_HAUT, $eval['declencheur']);
    }

    /** @test */
    public function une_cohorte_repartie_ne_sature_pas(): void
    {
        $eval = (new RiskSaturation(70.0, 90.0))->evaluer(['haut' => 20, 'moyen' => 30, 'bas' => 50], 100);

        $this->assertFalse($eval['is_saturated']);
        $this->assertNull($eval['declencheur']);
        $this->assertSame(50.0, $eval['total_pct']);
        $this->assertSame(50.0, $eval['bas_pct']);
    }

    /** @test */
    public function une_cohorte_vide_ne_sature_jamais(): void
    {
        $eval = (new RiskSaturation())->evaluer([], 0);

        $this->assertFalse($eval['is_saturated']);
        $this->assertSame(0.0, $eval['haut_pct']);
        $this->assertSame(0.0, $eval['total_pct']);
    }

    /** @test */
    public function les_seuils_sont_ceux_passes_et_remontes_dans_le_resultat(): void
    {
        $eval = (new RiskSaturation(50.0, 60.0))->evaluer(['haut' => 30, 'moyen' => 30, 'bas' => 40], 100);

        $this->assertTrue($eval['is_saturated'], 'haut + moyen = 60 % atteint le seuil total de 60 %');
        $this->assertSame(['haut_pct' => 50.0, 'total_pct' => 60.0], $eval['seuils']);
    }

    /** @test */
    public function les_surcharges_de_reglages_evitent_la_base(): void
    {
        $saturation = RiskSaturation::depuisReglages([
            RiskSaturation::REGLAGE_SEUIL_HAUT => 55,
            RiskSaturation::REGLAGE_SEUIL_TOTAL => 80,
        ]);

        $this->assertSame(55.0, $saturation->seuilHautPct);
        $this->assertSame(80.0, $saturation->seuilTotalPct);
    }

    /** @test */
    public function la_constante_historique_du_predicteur_suit_le_seuil_haut(): void
    {
        $this->assertSame(RiskSaturation::DEFAULT_SEUIL_HAUT_PCT, DefaultRiskPredictor::SATURATION_TRIGGER_PCT);
    }

    /** @test */
    public function le_diagnostic_recommande_une_commande_qui_existe(): void
    {
        $recommandations = $this->recommandations([
            'coverage' => ['coverage_pct' => 1.9, 'without_snapshot' => 206],
            'risk_saturation' => ['is_saturated' => false],
            'echeancier' => ['mode' => 'configured'],
            'annee_universitaire' => ['id' => 3, 'name' => '2025-2026'],
        ]);

        $texte = implode("\n", $recommandations);

        $this->assertStringContainsString('php artisan '.EcheanciersRecompute::NOM.' --annee=3', $texte);
        $this->assertStringContainsString('/api/cli/echeanciers/recompute', $texte);
        $this->assertStringContainsString("fiche financière", $texte, 'le diagnostic dit pourquoi les snapshots manquent');
        $this->assertSame('echeanciers:recompute', EcheanciersRecompute::NOM);
    }

    /** @test */
    public function une_cohorte_saturee_non_calibree_recommande_l_auto_calibration(): void
    {
        $recommandations = $this->recommandations([
            'coverage' => ['coverage_pct' => 100.0, 'without_snapshot' => 0],
            'risk_saturation' => [
                'is_saturated' => true,
                'declencheur' => RiskSaturation::DECLENCHEUR_TOTAL,
                'total_pct' => 100.0,
                'haut_risque_pct' => 60.0,
                'auto_calibrated' => false,
            ],
            'echeancier' => ['mode' => 'configured'],
        ]);

        $texte = implode("\n", $recommandations);

        $this->assertStringContainsString('analytics.default_risk.auto_calibrate=true', $texte);
        $this->assertStringContainsString('100.0 % de la cohorte en risque haut + moyen', $texte);
    }

    /** @test */
    public function une_saturation_sous_faible_couverture_est_signalee_comme_artefact(): void
    {
        $recommandations = $this->recommandations([
            'coverage' => ['coverage_pct' => 1.9, 'without_snapshot' => 206],
            'risk_saturation' => [
                'is_saturated' => true,
                'declencheur' => RiskSaturation::DECLENCHEUR_HAUT,
                'total_pct' => 100.0,
                'haut_risque_pct' => 75.0,
                'auto_calibrated' => true,
            ],
            'echeancier' => ['mode' => 'configured'],
        ]);

        $texte = implode("\n", $recommandations);

        $this->assertStringContainsString('auto-calibration active', $texte);
        $this->assertStringContainsString('artefact de la faible couverture', $texte);
    }

    /** @test */
    public function un_tenant_sain_n_a_rien_a_recommander(): void
    {
        $recommandations = $this->recommandations([
            'coverage' => ['coverage_pct' => 100.0, 'without_snapshot' => 0],
            'risk_saturation' => ['is_saturated' => false],
            'echeancier' => ['mode' => 'configured'],
        ]);

        $this->assertSame(['Tout est bon ✓'], $recommandations);
    }

    /**
     * @param  array<string, mixed>  $rapport
     * @return array<int, string>
     */
    private function recommandations(array $rapport): array
    {
        $methode = new ReflectionMethod(AnalyticsDiagnoseCommand::class, 'computeRecommendations');

        return $methode->invoke(new AnalyticsDiagnoseCommand(), $rapport);
    }
}
