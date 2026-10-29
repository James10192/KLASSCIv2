<?php

namespace Tests\Feature\Enseignant;

use Tests\TestCase;

class UpdateEnseignantRequestContractTest extends TestCase
{
    public function test_update_request_preserves_existing_hourly_rate_when_field_is_not_submitted(): void
    {
        $source = file_get_contents(app_path('Http/Requests/Enseignant/UpdateEnseignantRequest.php'));

        $this->assertStringContainsString('protected function prepareForValidation(): void', $source);
        $this->assertStringContainsString("!\$this->exists('taux_horaire')", $source);
        $this->assertStringContainsString("'taux_horaire' => \$enseignant->taux_horaire", $source);
    }

    public function test_edit_form_surfaces_errors_and_does_not_silently_block_submission(): void
    {
        $source = file_get_contents(resource_path('views/esbtp/enseignants/edit.blade.php'));

        $this->assertStringContainsString("@if(session('error'))", $source);
        $this->assertStringContainsString('role="alert"', $source);
        $this->assertStringContainsString('step="0.01"', $source);
        $this->assertStringNotContainsString('step="500"', $source);
        $this->assertStringContainsString('if (tauxField)', $source);
        $this->assertStringContainsString("teacherForm.addEventListener('invalid'", $source);
        $this->assertStringContainsString("submitBtn.setAttribute('aria-busy', 'true')", $source);
    }

    public function test_hidden_regime_fields_are_disabled_before_native_validation(): void
    {
        $source = file_get_contents(resource_path('views/esbtp/enseignants/edit.blade.php'));

        $this->assertStringContainsString("const tauxInput = document.getElementById('taux_horaire')", $source);
        $this->assertStringContainsString("const chargeInput = document.getElementById('charge_horaire_max_semaine')", $source);
        $this->assertStringContainsString('chargeInput.disabled = !permanent', $source);
        $this->assertStringContainsString('tauxInput.disabled = permanent', $source);
        $this->assertStringContainsString('Number(chargeInput.value) < 1', $source);
        $this->assertStringContainsString("chargeInput.value = '18'", $source);
        $this->assertStringContainsString('applyRegime(@json($selectedRegime))', $source);
    }
}
