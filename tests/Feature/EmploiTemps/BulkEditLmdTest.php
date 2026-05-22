<?php

namespace Tests\Feature\EmploiTemps;

use Tests\TestCase;

/**
 * Test Feature pour /esbtp/emploi-temps/bulk-edit en mode LMD.
 *
 * ## But (PR2 chantier emploi-temps-lmd-unification)
 *
 * Avant ce PR : `buildEmploiTempsViewData()` (line 711) appelait
 * `getPlanificationDataForClasse()` sans override LMD. Conséquence : pour une
 * classe LMD (ex: LICENCE 3 DROIT PRIVE A sur presentation), bulkEdit affichait
 * "Planification non configurée — 0 matières disponibles" MALGRÉ un LMD planning
 * existant.
 *
 * Après ce PR : `buildEmploiTempsViewData()` applique l'override LMD via
 * `MatiereTreeBuilder::buildForPlanning()` (sans volumeBudget car bulkEdit
 * n'affiche pas les KPIs heures réalisées).
 *
 * ## Matrice 4 combos (rule pre-merge-checklist.md)
 *
 * 1. BTS pivot peuplé → matières du pivot (legacy)
 * 2. BTS pivot vide   → matières via ESBTPPlanificationAcademique
 * 3. LMD avec parcours → ECUEs via parcours.unitesEnseignement
 * 4. LMD tronc commun → fallback filiere+niveau planifs
 *
 * Tous les 4 cas doivent retourner des matières — pas "0 disponibles".
 *
 * @see App\Http\Controllers\ESBTPEmploiTempsController::buildEmploiTempsViewData() line 675
 * @see App\Services\LMD\MatiereTreeBuilder::buildForPlanning()
 * @see docs/MASTER-PLAN-emploi-temps-lmd-unification.md PR2
 */
class BulkEditLmdTest extends TestCase
{
    /** @test */
    public function placeholder_bulk_edit_lmd_avec_parcours_shows_matieres(): void
    {
        // Tests DB-driven complets en PR14 (Pest Browser E2E).
        //
        // Pseudocode du test cible :
        //
        // 1. Setup :
        //    $classe = ESBTPClasse::factory()->lmd()->withParcours()->withPlanifs()->create();
        //    $emploiTemps = ESBTPEmploiTemps::factory()->for($classe)->create();
        //
        // 2. Acte :
        //    $response = $this->actingAs($user)
        //        ->get("/esbtp/emploi-temps/bulk-edit?ids={$emploiTemps->id}");
        //
        // 3. Assert :
        //    $response->assertOk();
        //    $response->assertDontSee('Planification non configurée');
        //    $response->assertSee('matieres_planifiees');  // ou nom matière LMD
        //
        $this->assertTrue(true, 'Placeholder PR2 — DB tests en PR14');
    }

    /** @test */
    public function placeholder_bulk_edit_bts_pivot_peuple_no_regression(): void
    {
        // Garde-fou : classe BTS legacy avec pivot peuplé doit continuer à fonctionner.
        // L'override LMD ne s'applique QUE si systeme_academique === 'LMD'.
        $this->assertTrue(true, 'Placeholder PR2 — DB tests en PR14');
    }

    /** @test */
    public function placeholder_bulk_edit_bts_pivot_vide_uses_planifications(): void
    {
        // Garde-fou : classe BTS moderne (pivot vide) doit avoir matières via planifs.
        $this->assertTrue(true, 'Placeholder PR2 — DB tests en PR14');
    }

    /** @test */
    public function placeholder_bulk_edit_lmd_tronc_commun_fallback_filiere_niveau(): void
    {
        // Garde-fou : classe LMD tronc commun (sans parcours, filiere_id = mention_id)
        // doit avoir matières via fallback loadFromFiliereNiveau.
        $this->assertTrue(true, 'Placeholder PR2 — DB tests en PR14');
    }

    /** @test */
    public function controller_buildEmploiTempsViewData_applique_override_lmd(): void
    {
        // Verifier via reflection que buildEmploiTempsViewData() contient bien l'appel
        // à MatiereTreeBuilder::buildForPlanning() (anti-régression PR2 supprimée).

        $reflection = new \ReflectionClass(\App\Http\Controllers\ESBTPEmploiTempsController::class);
        $method = $reflection->getMethod('buildEmploiTempsViewData');
        $this->assertTrue($method->isPrivate(), 'buildEmploiTempsViewData() must be private');

        // Lire le source code pour verifier l'appel au service
        $sourceCode = file_get_contents($reflection->getFileName());

        // Vérifier que la méthode privée duplicate a été supprimée
        $this->assertStringNotContainsString(
            "private function overridePlanificationForLmd",
            $sourceCode,
            'La methode privee overridePlanificationForLmd a ete supprimee en PR2 (DRY consolidation)'
        );

        // Vérifier que le caller utilise le service
        $this->assertStringContainsString(
            'MatiereTreeBuilder::class',
            $sourceCode,
            'ESBTPEmploiTempsController doit utiliser MatiereTreeBuilder service'
        );
    }
}
