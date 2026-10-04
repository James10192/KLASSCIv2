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
}
