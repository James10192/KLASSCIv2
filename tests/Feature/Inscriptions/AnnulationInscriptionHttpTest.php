<?php

namespace Tests\Feature\Inscriptions;

use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AnnulationInscriptionHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;
    private ESBTPInscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['admin.access', 'inscriptions.cancel', 'inscriptions.validate'] as $nom) {
            Permission::findOrCreate($nom, 'web');
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(['admin.access', 'inscriptions.cancel']);
        $this->assertTrue($this->agent->can('inscriptions.cancel'));
        $this->assertFalse($this->agent->can('inscriptions.validate'));
        $this->inscription = ESBTPInscription::factory()->create([
            'status' => 'en_attente',
            'workflow_step' => 'documents_complets',
        ]);
    }

    private function envoyer(array $donnees)
    {
        return $this->actingAs($this->agent)->call(
            'POST',
            route('esbtp.inscriptions.annuler', $this->inscription),
            ['_method' => 'PUT'] + $donnees,
            [], [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
    }

    public function test_annulation_individuelle_avec_motif_urlencode_est_effective(): void
    {
        $this->envoyer(['motif' => 'Annulation demandée par la scolarité'])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame('annulée', $this->inscription->fresh()->status);
        $this->assertStringContainsString('Annulation demandée', $this->inscription->fresh()->observations);
    }

    public function test_motif_absent_refuse_sans_annuler(): void
    {
        $this->envoyer([])->assertStatus(422)->assertJsonValidationErrors('motif');
        $this->assertSame('en_attente', $this->inscription->fresh()->status);
    }

    public function test_validation_seule_ne_donne_pas_le_droit_d_annuler(): void
    {
        $valideur = User::factory()->create();
        $valideur->givePermissionTo(['admin.access', 'inscriptions.validate']);
        $this->actingAs($valideur)->call(
            'POST',
            route('esbtp.inscriptions.annuler', $this->inscription),
            ['_method' => 'PUT', 'motif' => 'Test de refus ciblé'],
            [], [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        )->assertForbidden();
        $this->assertSame('en_attente', $this->inscription->fresh()->status);
    }

    public function test_double_annulation_renvoie_422_sans_transaction_ouverte(): void
    {
        $this->envoyer(['motif' => 'Premier passage'])->assertOk();
        $niveau = DB::transactionLevel();
        $this->envoyer(['motif' => 'Nouvelle demande'])->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertSame($niveau, DB::transactionLevel());
    }
    public function test_accept_json_sans_entete_ajax_retourne_json_apres_annulation(): void
    {
        $this->actingAs($this->agent)->call(
            'POST',
            route('esbtp.inscriptions.annuler', $this->inscription),
            ['_method' => 'PUT', 'motif' => 'Traitement autorisé sans header XMLHttpRequest'],
            [], [],
            ['HTTP_ACCEPT' => 'application/json']
        )->assertOk()->assertJsonPath('success', true);
    }

    public function test_exception_technique_ne_divulgue_aucun_detail_interne_en_json(): void
    {
        $service = \Mockery::mock(\App\Services\ESBTPInscriptionService::class);
        $service->shouldReceive('annulerInscription')->once()
            ->andThrow(new \RuntimeException('SQLSTATE[HY000] password=secret'));
        $this->app->instance(\App\Services\ESBTPInscriptionService::class, $service);

        $reponse = $this->actingAs($this->agent)->call(
            'POST', route('esbtp.inscriptions.annuler', $this->inscription),
            ['_method' => 'PUT', 'motif' => 'Une annulation simulée'], [], [],
            ['HTTP_ACCEPT' => 'application/json']
        );
        $reponse->assertStatus(500)->assertJsonPath('success', false);
        $this->assertStringNotContainsString('SQLSTATE', $reponse->getContent());
        $this->assertStringNotContainsString('secret', $reponse->getContent());
    }
}
