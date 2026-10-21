<?php

namespace Tests\Feature\Inscriptions;

use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AnnulationInscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_annulation_valide_son_motif_puis_rejette_un_second_traitement_sans_transaction_ouverte(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('inscriptions.cancel', 'web');
        $agent = User::factory()->create();
        $agent->givePermissionTo(['admin.access', 'inscriptions.cancel']);
        $classe = ESBTPClasse::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'status' => 'en_attente',
            'workflow_step' => 'documents_complets',
        ]);

        $niveauTransactionInitial = DB::transactionLevel();
        $this->actingAs($agent)->putJson(route('esbtp.inscriptions.annuler', $inscription), [])
            ->assertUnprocessable()->assertJsonValidationErrors(['motif']);
        $this->assertSame('en_attente', $inscription->fresh()->status);

        $this->actingAs($agent)->put(route('esbtp.inscriptions.annuler', $inscription), [
            'motif' => 'Erreur administrative signalée et contrôlée',
        ])->assertRedirect();
        $this->assertSame('annulée', $inscription->fresh()->status);

        $this->actingAs($agent)->put(route('esbtp.inscriptions.annuler', $inscription), [
            'motif' => 'Seconde tentative identique',
        ])->assertRedirect();
        $this->assertSame($niveauTransactionInitial, DB::transactionLevel(), 'Une annulation refusée ne laisse pas de transaction ouverte');
        $this->assertSame(1, substr_count((string) $inscription->fresh()->observations, 'Annulée le'));
    }

    public function test_un_agent_sans_permission_ne_peut_pas_annuler(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('inscriptions.cancel', 'web');
        $agent = User::factory()->create();
        $agent->givePermissionTo('admin.access');
        $classe = ESBTPClasse::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'status' => 'en_attente',
        ]);

        $this->actingAs($agent)->putJson(route('esbtp.inscriptions.annuler', $inscription), [
            'motif' => 'Tentative interdite',
        ])->assertForbidden();
        $this->assertSame('en_attente', $inscription->fresh()->status);
    }
}
