<?php

namespace Tests\Feature\Dashboard;

use App\Http\Controllers\AgentInscriptionDashboardController;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * « Validés ce jour » se lit sur la date de validation. Il se lisait sur
 * updated_at : une operation de masse qui touche les dossiers les comptait tous
 * (2175 a ISLG un matin, pour un agent qui n'en avait valide aucun).
 */
class AgentInscriptionValidesCeJourTest extends TestCase
{
    use RefreshDatabase;

    public function test_seule_une_validation_du_jour_compte(): void
    {
        Permission::findOrCreate('identity.enrollment_officer', 'web');
        $agent = User::factory()->create();
        $agent->givePermissionTo('identity.enrollment_officer');
        $this->actingAs($agent);

        ESBTPInscription::factory()->create(['status' => 'active', 'workflow_step' => 'etudiant_cree', 'date_validation' => now()->toDateString()]);
        // Validée il y a un mois, touchée aujourd'hui par un recalcul de masse.
        ESBTPInscription::factory()->create(['status' => 'active', 'workflow_step' => 'etudiant_cree', 'date_validation' => now()->subMonth()->toDateString(), 'updated_at' => now()]);

        $vue = app(AgentInscriptionDashboardController::class)->index();

        $this->assertSame(1, $vue->getData()['validatedToday']);
    }
}
