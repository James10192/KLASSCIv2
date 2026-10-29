<?php

namespace Tests\Unit\Services;

use App\Services\LMD\MatiereTreeBuilder;
use Tests\TestCase;

/**
 * Tests Unit pour App\Services\LMD\MatiereTreeBuilder.
 *
 * Couvre l'API publique (rule lmd-bts-matieres-single-source.md) :
 * - buildForPlanning() — sans volumeBudget
 * - buildWithVolumeBudget() — avec volumeBudget (VolumeBudgetService)
 * - loadLmdMatieresForClasse() — helper bas-niveau
 * - forClasse() — groupage UE → ECUE
 * - overridePlanificationForLmd() — alias deprecated rétrocompat
 *
 * ## Matrice 4 combos systématique (rule pre-merge-checklist.md)
 *
 * Chaque test critique tourne sur :
 * 1. BTS pivot peuplé (legacy)
 * 2. BTS pivot vide (BTS moderne)
 * 3. LMD avec parcours
 * 4. LMD tronc commun (mention seulement)
 *
 * Note : Tests RefreshDatabase requis pour les tests Feature de la matrice.
 * Cette suite Unit teste UNIQUEMENT l'existence et la signature de l'API.
 *
 * @see App\Services\LMD\MatiereTreeBuilder
 * @see memory/feedback_matiere_tree_builder_canonical.md
 * @see docs/MASTER-PLAN-emploi-temps-lmd-unification.md PR1
 */
class MatiereTreeBuilderTest extends TestCase
{
    /** @test */
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(MatiereTreeBuilder::class);
        $this->assertInstanceOf(MatiereTreeBuilder::class, $service);
    }

    /** @test */
    public function service_exposes_buildForPlanning_public_method(): void
    {
        $reflection = new \ReflectionClass(MatiereTreeBuilder::class);
        $this->assertTrue(
            $reflection->hasMethod('buildForPlanning'),
            'MatiereTreeBuilder must expose buildForPlanning() (rule lmd-bts-matieres-single-source.md)'
        );
        $method = $reflection->getMethod('buildForPlanning');
        $this->assertTrue($method->isPublic(), 'buildForPlanning() must be public');

        // Signature : (array $planificationData, ESBTPClasse $classe) : array
        $params = $method->getParameters();
        $this->assertCount(2, $params, 'buildForPlanning() must have 2 params (planificationData, classe)');
        $this->assertSame('planificationData', $params[0]->getName());
        $this->assertSame('classe', $params[1]->getName());
    }

    /** @test */
    public function service_exposes_buildWithVolumeBudget_public_method(): void
    {
        $reflection = new \ReflectionClass(MatiereTreeBuilder::class);
        $this->assertTrue(
            $reflection->hasMethod('buildWithVolumeBudget'),
            'MatiereTreeBuilder must expose buildWithVolumeBudget()'
        );
        $method = $reflection->getMethod('buildWithVolumeBudget');
        $this->assertTrue($method->isPublic(), 'buildWithVolumeBudget() must be public');

        // Signature : (array, ESBTPClasse, ?ESBTPAnneeUniversitaire = null) : array
        $params = $method->getParameters();
        $this->assertCount(3, $params, 'buildWithVolumeBudget() must have 3 params');
        $this->assertSame('planificationData', $params[0]->getName());
        $this->assertSame('classe', $params[1]->getName());
        $this->assertSame('annee', $params[2]->getName());
        $this->assertTrue($params[2]->isOptional(), '$annee must be optional (defaults to current)');
    }

    /** @test */
    public function deprecated_alias_overridePlanificationForLmd_still_works(): void
    {
        $reflection = new \ReflectionClass(MatiereTreeBuilder::class);
        $this->assertTrue(
            $reflection->hasMethod('overridePlanificationForLmd'),
            'Alias deprecated must still exist for backward compat (strangler fig)'
        );
        $method = $reflection->getMethod('overridePlanificationForLmd');
        $this->assertTrue($method->isPublic());

        // Vérifier que le DocBlock contient @deprecated
        $docComment = $method->getDocComment();
        $this->assertStringContainsString(
            '@deprecated',
            $docComment ?: '',
            'Alias overridePlanificationForLmd must be marked @deprecated'
        );
    }

    /** @test */
    public function service_helper_loadLmdMatieresForClasse_is_public(): void
    {
        $reflection = new \ReflectionClass(MatiereTreeBuilder::class);
        $this->assertTrue($reflection->hasMethod('loadLmdMatieresForClasse'));
        $method = $reflection->getMethod('loadLmdMatieresForClasse');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function service_uses_volumeBudgetService_via_di(): void
    {
        // Vérifier que la classe importe bien VolumeBudgetService (DIP — pas hardcode)
        $reflection = new \ReflectionClass(MatiereTreeBuilder::class);
        $sourceCode = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString(
            'use App\Services\VolumeBudgetService;',
            $sourceCode,
            'MatiereTreeBuilder must import VolumeBudgetService (rule lmd-bts-matieres-single-source.md)'
        );
    }

    /** @test */
    public function service_documents_canonical_pattern_in_docblock(): void
    {
        $reflection = new \ReflectionClass(MatiereTreeBuilder::class);
        $docComment = $reflection->getDocComment() ?: '';

        $this->assertStringContainsString(
            'Single Source of Truth',
            $docComment,
            'Class docblock must mention SSOT pattern'
        );
        $this->assertStringContainsString(
            'buildForPlanning',
            $docComment,
            'Class docblock must mention buildForPlanning() method'
        );
        $this->assertStringContainsString(
            'buildWithVolumeBudget',
            $docComment,
            'Class docblock must mention buildWithVolumeBudget() method'
        );
    }

    /**
     * Tests fonctionnels avec DB seedée — placeholder pour Feature tests en PR14.
     *
     * @test
     * @group skip-pr1
     */
    public function buildForPlanning_returns_matieres_for_4_combos_DB_required(): void
    {
        $this->markTestSkipped('Tests fonctionnels DB en PR14 — voir tests/Feature/Services/MatiereTreeBuilderFeatureTest.php');
    }
}
