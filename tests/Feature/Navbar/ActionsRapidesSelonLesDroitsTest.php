<?php

namespace Tests\Feature\Navbar;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Les actions rapides de la barre du haut ne proposent que des pages qui
 * s'ouvrent. Relevé sur la caisse d'ISLG (septembre 2026) : un caissier, qui
 * porte `admin.access`, se voyait proposer « Nouvelle classe » et « Créer
 * examen », deux pages qui lui répondaient par un refus d'accès.
 */
class ActionsRapidesSelonLesDroitsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (['admin.access', 'paiements.create', 'module.caisse.access', 'cash_session.manage', 'classes.create', 'evaluations.create', 'annonces.view', 'annonces.create', 'annonces.edit'] as $nom) {
            Permission::findOrCreate($nom, 'web');
        }
        Cache::flush();
    }

    private function titres(User $user): array
    {
        return collect($this->actingAs($user)->getJson(route('navbar.quick-actions'))->assertOk()->json('actions'))
            ->pluck('title')->all();
    }

    public function test_le_caissier_ne_voit_que_ce_qu_il_peut_ouvrir(): void
    {
        $caissier = User::factory()->create();
        $caissier->givePermissionTo(['admin.access', 'paiements.create', 'module.caisse.access', 'cash_session.manage', 'annonces.view']);

        $titres = $this->titres($caissier);

        $this->assertContains('Encaisser', $titres);
        $this->assertContains('Ma caisse', $titres);
        $this->assertNotContains('Nouvelle classe', $titres);
        $this->assertNotContains('Créer examen', $titres);
        $this->assertNotContains('Saisie notes', $titres);
        // Lire les annonces ne donne pas le droit d'en publier.
        $this->assertNotContains('Nouvelle annonce', $titres);
    }

    public function test_qui_porte_la_permission_retrouve_l_action(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->givePermissionTo(['admin.access', 'classes.create', 'evaluations.create']);

        $titres = $this->titres($gestionnaire);

        $this->assertContains('Nouvelle classe', $titres);
        $this->assertContains('Créer examen', $titres);
        $this->assertNotContains('Encaisser', $titres);
    }

    public function test_lire_les_annonces_ne_permet_pas_d_en_publier(): void
    {
        $lecteur = User::factory()->create();
        $lecteur->givePermissionTo(['admin.access', 'annonces.view']);

        $this->actingAs($lecteur)->get(route('esbtp.annonces.create'))->assertForbidden();
        $this->actingAs($lecteur)->post(route('esbtp.annonces.store'), ['titre' => 'x'])->assertForbidden();
    }

    public function test_sans_droit_sur_les_evaluations_le_formulaire_est_refuse(): void
    {
        $caissier = User::factory()->create();
        $caissier->givePermissionTo(['admin.access', 'paiements.create']);

        $this->actingAs($caissier)->get(route('esbtp.evaluations.create'))->assertForbidden();
        $this->actingAs($caissier)->post(route('esbtp.evaluations.store'), [])->assertForbidden();
    }
}
