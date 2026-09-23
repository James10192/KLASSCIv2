<?php

namespace Tests\Feature\RendezVous;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvCreneau;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * L'ecran repond en JSON, sans rechargement, et dit pourquoi il refuse.
 */
class EcranRendezVousTest extends TestCase
{
    use RefreshDatabase;

    private User $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Cache::flush();

        foreach (['admin.access', 'inscriptions.rdv.view', 'inscriptions.rdv.manage', 'inscriptions.rdv.configure'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->gestionnaire = User::factory()->create();
        $this->gestionnaire->givePermissionTo(['admin.access', 'inscriptions.rdv.view', 'inscriptions.rdv.manage', 'inscriptions.rdv.configure']);
    }

    public function test_les_fragments_se_rechargent_en_json_et_nomment_le_canal_ferme(): void
    {
        $reponse = $this->actingAs($this->gestionnaire)
            ->getJson(route('esbtp.rendez-vous.index', ['fragment' => 1]));

        $reponse->assertOk()->assertJsonStructure(['kpis', 'chaine', 'tableau', 'reglages']);
        $this->assertStringContainsString('Prise de rendez-vous fermée', $reponse->json('chaine'));
    }

    public function test_une_tolerance_de_retard_illisible_est_refusee(): void
    {
        $this->actingAs($this->gestionnaire)
            ->postJson(route('esbtp.rendez-vous.reglages'), ['inscriptions_rdv_grace_no_show_minutes' => 'quinze'])
            ->assertStatus(422)->assertJsonFragment(['message' => 'La tolérance de retard doit être un nombre entier de minutes.']);

        $this->actingAs($this->gestionnaire)
            ->postJson(route('esbtp.rendez-vous.reglages'), ['inscriptions_rdv_grace_no_show_minutes' => '20'])
            ->assertOk();
        $this->assertSame(20, app(\App\Services\RendezVous\RendezVousReglages::class)->graceMinutes());
    }

    public function test_la_page_complete_s_affiche(): void
    {
        $this->actingAs($this->gestionnaire)
            ->get(route('esbtp.rendez-vous.index'))
            ->assertOk()
            ->assertSee('Rendez-vous d\'inscription')
            ->assertSee('Commencez par les réglages');
    }

    public function test_placer_sur_un_canal_ferme_est_un_refus_explique(): void
    {
        $this->actingAs($this->gestionnaire)
            ->postJson(route('esbtp.rendez-vous.placer'))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'La prise de rendez-vous est fermée. Ouvrez-la dans les réglages avant de placer les dossiers.']);
    }

    public function test_fermer_un_creneau_repond_en_json(): void
    {
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => ESBTPAnneeUniversitaire::factory()->create()->id,
            'date' => now()->addDays(2)->toDateString(), 'heure_debut' => '08:00:00',
            'heure_fin' => '08:30:00', 'capacite' => 5, 'ouvert' => true,
        ]);

        $this->actingAs($this->gestionnaire)
            ->postJson(route('esbtp.rendez-vous.fermer', $creneau))
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertFalse($creneau->fresh()->ouvert);
    }

    public function test_la_lecture_seule_ne_peut_pas_placer(): void
    {
        $lecteur = User::factory()->create();
        $lecteur->givePermissionTo(['admin.access', 'inscriptions.rdv.view']);

        $this->actingAs($lecteur)
            ->postJson(route('esbtp.rendez-vous.placer'))
            ->assertForbidden();
    }
}
