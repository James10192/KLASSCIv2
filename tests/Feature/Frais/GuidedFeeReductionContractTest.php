<?php

namespace Tests\Feature\Frais;

use Tests\TestCase;

class GuidedFeeReductionContractTest extends TestCase
{
    public function test_reduction_reuses_permission_scoped_subscription_update(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPInscriptionPaiementController.php'));
        $student = file_get_contents(resource_path('views/esbtp/etudiants/show.blade.php'));

        $this->assertStringContainsString("->name('inscriptions.update-subscription')", $routes);
        $this->assertStringContainsString("->middleware('permission:inscriptions.edit')", $routes);
        $this->assertStringContainsString("if (\$subscription->inscription_id !== \$inscription->id)", $controller);
        $this->assertStringContainsString("netPaidForInscription(", $controller);
        $this->assertStringContainsString("lockForUpdate()", $controller);
        $this->assertStringContainsString("'reason' => ['required', 'string', 'min:10', 'max:1000']", $controller);
        $this->assertStringContainsString('Accorder une réduction', $student);
        $this->assertStringContainsString('studentFeeReductionModal', $student);
        $this->assertSame(1, substr_count($student, 'id="studentFeeReductionModal"'));
        $this->assertStringContainsString("data-inscription-id", $student);
        $this->assertStringContainsString("data-inscription-label", $student);
    }
}
