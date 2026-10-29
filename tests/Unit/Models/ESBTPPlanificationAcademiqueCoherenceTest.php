<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPPlanificationAcademique;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie que l'ajout des colonnes `volume_horaire_projet` et `volume_horaire_tpe`
 * (UEMOA LMD) ne casse pas la validation pour les rangs BTS legacy
 * où ces colonnes restent à 0 (default DB).
 */
class ESBTPPlanificationAcademiqueCoherenceTest extends TestCase
{
    public function test_bts_legacy_planning_remains_coherent_with_zero_lmd_volumes(): void
    {
        $planif = new ESBTPPlanificationAcademique([
            'volume_horaire_total' => 50,
            'volume_horaire_cm' => 20,
            'volume_horaire_td' => 20,
            'volume_horaire_tp' => 10,
            'volume_horaire_projet' => 0,
            'volume_horaire_tpe' => 0,
            'enseignant_principal_id' => 1,
        ]);

        $erreurs = $planif->validerCoherence();

        $this->assertNotContains(
            'La somme des volumes horaires détaillés (50h) ne correspond pas au total (50h)',
            $erreurs,
            'BTS legacy planning with TP/TD/CM = total should remain valid after LMD migration'
        );

        // Aucune erreur de cohérence volume horaire pour ce cas
        $volumeErrors = array_filter($erreurs, fn ($e) => str_contains($e, 'volumes horaires'));
        $this->assertEmpty($volumeErrors);
    }

    public function test_lmd_planning_with_projet_and_tpe_validates_correctly(): void
    {
        $planif = new ESBTPPlanificationAcademique([
            'volume_horaire_total' => 75,
            'volume_horaire_cm' => 15,
            'volume_horaire_td' => 15,
            'volume_horaire_tp' => 0,
            'volume_horaire_projet' => 15,
            'volume_horaire_tpe' => 30,
            'enseignant_principal_id' => 1,
        ]);

        $erreurs = $planif->validerCoherence();

        $volumeErrors = array_filter($erreurs, fn ($e) => str_contains($e, 'volumes horaires'));
        $this->assertEmpty($volumeErrors);
    }

    public function test_planning_with_volume_mismatch_returns_error(): void
    {
        $planif = new ESBTPPlanificationAcademique([
            'volume_horaire_total' => 50,
            'volume_horaire_cm' => 10,
            'volume_horaire_td' => 10,
            'volume_horaire_tp' => 10,
            'volume_horaire_projet' => 0,
            'volume_horaire_tpe' => 0,
            'enseignant_principal_id' => 1,
        ]);

        $erreurs = $planif->validerCoherence();
        $volumeErrors = array_filter($erreurs, fn ($e) => str_contains($e, 'volumes horaires'));

        $this->assertNotEmpty(
            $volumeErrors,
            'Sum of volumes (30h) does not equal total (50h) — must report an error'
        );
    }

    public function test_volume_horaire_total_calcule_includes_projet_and_tpe(): void
    {
        $planif = new ESBTPPlanificationAcademique([
            'volume_horaire_cm' => 10,
            'volume_horaire_td' => 5,
            'volume_horaire_tp' => 5,
            'volume_horaire_projet' => 5,
            'volume_horaire_tpe' => 25,
        ]);

        $this->assertSame(50, $planif->volume_horaire_total_calcule);
    }

    public function test_volume_horaire_total_calcule_handles_null_projet_tpe_for_legacy_rows(): void
    {
        // Les rangs créés avant la migration ont volume_horaire_projet/tpe = NULL
        // (avant que le default 0 ne soit appliqué via Schema::hasColumn).
        $planif = new ESBTPPlanificationAcademique([
            'volume_horaire_cm' => 20,
            'volume_horaire_td' => 20,
            'volume_horaire_tp' => 10,
        ]);

        $this->assertSame(50, $planif->volume_horaire_total_calcule);
    }

    public function test_missing_enseignant_principal_returns_error(): void
    {
        // BTS legacy rows commonly have enseignant_principal_id = NULL.
        // The validator should consistently flag this rather than silently passing.
        $planif = new ESBTPPlanificationAcademique([
            'volume_horaire_total' => 30,
            'volume_horaire_cm' => 10,
            'volume_horaire_td' => 10,
            'volume_horaire_tp' => 10,
            'enseignant_principal_id' => null,
        ]);

        $erreurs = $planif->validerCoherence();

        $this->assertContains('Un enseignant principal doit être assigné', $erreurs);
    }
}
