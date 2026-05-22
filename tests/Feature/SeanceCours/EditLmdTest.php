<?php

namespace Tests\Feature\SeanceCours;

use Tests\TestCase;

/**
 * Test Feature pour /esbtp/seances-cours/{id}/edit en mode LMD.
 *
 * ## But (PR3 chantier emploi-temps-lmd-unification)
 *
 * Avant ce PR : `ESBTPSeanceCoursController::edit()` ligne 828 appelait
 * `getPlanificationDataForClasse()` SANS override LMD → 0 matières pour LMD.
 *
 * Après ce PR : applique `MatiereTreeBuilder::buildForPlanning()` quand classe LMD.
 *
 * @see App\Http\Controllers\ESBTPSeanceCoursController::edit() line ~828
 * @see docs/MASTER-PLAN-emploi-temps-lmd-unification.md PR3
 */
class EditLmdTest extends TestCase
{
    /** @test */
    public function edit_method_applies_lmd_override(): void
    {
        $reflection = new \ReflectionClass(\App\Http\Controllers\ESBTPSeanceCoursController::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString(
            "MatiereTreeBuilder::class",
            $source,
            'ESBTPSeanceCoursController doit utiliser MatiereTreeBuilder (PR1+PR3)'
        );

        // Vérifier que les 2 appels (create + edit) appliquent l'override LMD
        $matchCount = substr_count(
            $source,
            "buildForPlanning(\$planificationData, \$emploiTemps->classe)"
        );
        $this->assertGreaterThanOrEqual(2, $matchCount,
            'buildForPlanning doit etre appele dans create() ET edit() (au moins 2 occurrences)'
        );
    }
}
