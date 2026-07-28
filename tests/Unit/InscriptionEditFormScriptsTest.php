<?php

namespace Tests\Unit;

use Tests\TestCase;

class InscriptionEditFormScriptsTest extends TestCase
{
    public function test_lmd_class_options_are_filtered_on_initial_load(): void
    {
        $script = file_get_contents(resource_path('views/esbtp/inscriptions/partials/edit-form-scripts.blade.php'));

        $initialStatePosition = strpos($script, '// État initial');
        $applyModePosition = strpos($script, 'applyModeFromNiveau();', $initialStatePosition);
        $filterPosition = strpos($script, 'filterClasses();', $applyModePosition);
        $placesPosition = strpos($script, "updatePlacesInfo($('#classe_id').val());", $filterPosition);

        $this->assertIsInt($initialStatePosition);
        $this->assertIsInt($applyModePosition);
        $this->assertIsInt($filterPosition);
        $this->assertIsInt($placesPosition);
    }
}
