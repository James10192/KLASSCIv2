<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InscriptionShowPaiementsInlineTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (['inscriptions.view','finances.etudiants.voir','paiements.view','paiements.validate','identity.enrollment_officer'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Cache::flush();
    }

    public function test_la_fiche_affiche_la_validation_inline_sans_lien_vers_paiements_index(): void
    {
        $inscription = ESBTPInscription::factory()->create();
        $paiement = ESBTPPaiement::factory()->pour($inscription)->create([
            'status' => 'en_attente',
            'numero_recu' => 'REC-INLINE-001',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['inscriptions.view','finances.etudiants.voir','paiements.view','paiements.validate','identity.enrollment_officer']);

        $response = $this->actingAs($user)->get(route('esbtp.inscriptions.show', $inscription));

        $response->assertOk()
            ->assertSee('id="inscription-payments-section"', false)
            ->assertSee('REC-INLINE-001')
            ->assertSee('js-paiement-valider-inline', false)
            ->assertSee('data-paiement-id="'.$paiement->id.'"', false)
            ->assertSee('data-action-url="'.route('esbtp.paiements.valider', $paiement->id).'"', false)
            ->assertDontSee(route('esbtp.paiements.index').'?search=', false);
    }

    public function test_le_bouton_inline_disparait_sans_permission_de_validation(): void
    {
        $inscription = ESBTPInscription::factory()->create();
        ESBTPPaiement::factory()->pour($inscription)->create(['status' => 'en_attente','numero_recu' => 'REC-INLINE-002']);

        $user = User::factory()->create();
        $user->givePermissionTo(['inscriptions.view','finances.etudiants.voir','paiements.view','identity.enrollment_officer']);

        $this->actingAs($user)
            ->get(route('esbtp.inscriptions.show', $inscription))
            ->assertOk()
            ->assertSee('REC-INLINE-002')
            ->assertDontSee('js-paiement-valider-inline', false);
    }

    public function test_validation_ajax_depuis_la_fiche_retourne_json_et_change_le_statut(): void
    {
        $inscription = ESBTPInscription::factory()->create();
        $paiement = ESBTPPaiement::factory()->pour($inscription)->create(['status' => 'en_attente']);
        $validateur = User::factory()->create();
        $validateur->givePermissionTo(['paiements.validate','identity.enrollment_officer']);
        $paiement->forceFill(['created_by' => User::factory()->create()->id])->save();

        $this->actingAs($validateur)
            ->postJson(route('esbtp.paiements.valider', $paiement->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'validé');

        $this->assertSame('validé', $paiement->fresh()->status);
        $this->assertSame($validateur->id, $paiement->fresh()->validateur_id);
    }
}
