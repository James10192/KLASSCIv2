<?php

namespace Tests\Feature;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisOption;
use App\Models\ESBTPOptionAssignment;
use App\Services\FraisManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraisOptionAutoAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeOptionalCategory(): ESBTPFraisCategory
    {
        return ESBTPFraisCategory::create([
            'name' => 'CANTINE',
            'code' => 'CANTINE',
            'is_mandatory' => false,
            'is_active' => true,
        ]);
    }

    public function test_global_option_auto_creates_all_assignment_on_creation(): void
    {
        $category = $this->makeOptionalCategory();

        $option = ESBTPFraisOption::create([
            'configuration_id' => null,
            'frais_category_id' => $category->id,
            'name' => 'Cantine 5 jours',
            'additional_amount' => 50000,
            'is_default' => false,
            'is_active' => true,
            'option_type' => 'global',
        ]);

        $assignments = $option->fresh()->assignments;

        $this->assertCount(1, $assignments, 'Une assignation doit être auto-créée');
        $this->assertSame('all', $assignments->first()->assignment_type);
        $this->assertNull($assignments->first()->filiere_id);
        $this->assertNull($assignments->first()->niveau_id);
        $this->assertTrue((bool) $assignments->first()->is_active);
    }

    public function test_class_based_option_does_not_auto_create_assignment(): void
    {
        $category = $this->makeOptionalCategory();

        $option = ESBTPFraisOption::create([
            'configuration_id' => 1,
            'frais_category_id' => $category->id,
            'name' => 'Option BTS1',
            'additional_amount' => 0,
            'is_default' => false,
            'is_active' => true,
            'option_type' => 'class_based',
        ]);

        $this->assertCount(0, $option->fresh()->assignments);
    }

    public function test_orphan_option_is_excluded_from_student_facing_service(): void
    {
        $category = $this->makeOptionalCategory();

        $option = ESBTPFraisOption::create([
            'configuration_id' => null,
            'frais_category_id' => $category->id,
            'name' => 'Cantine orpheline',
            'additional_amount' => 30000,
            'is_default' => false,
            'is_active' => true,
            'option_type' => 'global',
        ]);

        // Suppression manuelle de toutes les assignations → option devient orpheline
        ESBTPOptionAssignment::where('option_id', $option->id)->delete();
        $this->assertCount(0, $option->fresh()->assignments);

        // Le service student-facing l'exclut
        $service = app(FraisManagementService::class);
        $globalOptions = $service->getGlobalOptions($category);

        $this->assertNotContains($option->id, $globalOptions->pluck('id')->all());

        // L'admin la voit toujours via la relation directe (pour pouvoir corriger)
        $this->assertContains($option->id, $category->options()->pluck('id')->all());
    }

    public function test_caller_explicit_assignment_is_not_duplicated(): void
    {
        $category = $this->makeOptionalCategory();

        $option = ESBTPFraisOption::create([
            'configuration_id' => null,
            'frais_category_id' => $category->id,
            'name' => 'Avec assignation explicite',
            'additional_amount' => 25000,
            'is_default' => false,
            'is_active' => true,
            'option_type' => 'global',
        ]);

        // Caller redéfinit immédiatement les assignations
        ESBTPOptionAssignment::updateAssignmentsForOption(
            $option->id,
            'filiere',
            [1],
            []
        );

        $assignments = $option->fresh()->assignments;
        // L'auto-create du hook a inséré 'all', updateAssignmentsForOption
        // a tout supprimé puis recréé 'filiere' → 1 seule assignation type filiere.
        $this->assertCount(1, $assignments);
        $this->assertSame('filiere', $assignments->first()->assignment_type);
    }
}
