<?php

namespace Tests\Feature\Classes;

use App\Models\ESBTPClasse;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Modifier des classes à la suite depuis la liste : l'enregistrement renvoie
 * la carte à jour, pour éviter un second aller-retour par classe.
 */
class ModificationEnChaineTest extends TestCase
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
        foreach (['classes.edit', 'classes.view', 'classes.delete', 'admin.access'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Cache::flush();
    }

    public function test_l_enregistrement_renvoie_la_carte_a_jour(): void
    {
        $classe = ESBTPClasse::factory()->create(['name' => 'Ancien nom']);
        $user = User::factory()->create();
        $user->givePermissionTo(['classes.edit', 'classes.view', 'admin.access']);

        $reponse = $this->actingAs($user)->putJson(route('esbtp.classes.update', $classe), [
            'name' => 'Nouveau nom', 'code' => $classe->code, 'filiere_id' => $classe->filiere_id,
            'niveau_etude_id' => $classe->niveau_etude_id, 'annee_universitaire_id' => $classe->annee_universitaire_id,
            'places_totales' => 40, 'is_active' => 1, 'is_ajax' => '1',
        ])->assertOk()->assertJsonPath('success', true);

        $html = $reponse->json('html');
        $this->assertIsString($html);
        $this->assertStringContainsString('data-classe-id="'.$classe->id.'"', $html);
        $this->assertStringContainsString('Nouveau nom', $html);
    }

    public function test_une_carte_qui_ne_se_rend_pas_ne_fait_pas_croire_a_un_echec(): void
    {
        $classe = ESBTPClasse::factory()->create(['name' => 'Ancien nom']);
        $user = User::factory()->create();
        $user->givePermissionTo(['classes.edit', 'classes.view', 'admin.access']);
        \Illuminate\Support\Facades\View::composer('esbtp.classes.partials.classe-card', function () {
            throw new \RuntimeException('rendu impossible');
        });

        $this->actingAs($user)->putJson(route('esbtp.classes.update', $classe), [
            'name' => 'Nouveau nom', 'code' => $classe->code, 'filiere_id' => $classe->filiere_id,
            'niveau_etude_id' => $classe->niveau_etude_id, 'annee_universitaire_id' => $classe->annee_universitaire_id,
            'places_totales' => 40, 'is_active' => 1, 'is_ajax' => '1',
        ])->assertOk()->assertJsonPath('success', true)->assertJsonPath('html', null);

        $this->assertSame('Nouveau nom', $classe->fresh()->name);
    }

    public function test_la_liste_porte_la_fenetre_d_edition_a_la_chaine(): void
    {
        ESBTPClasse::factory()->count(2)->create();
        $user = User::factory()->create();
        $user->givePermissionTo(['classes.edit', 'classes.view', 'admin.access']);

        $this->actingAs($user)->get(route('esbtp.classes.index'))
            ->assertOk()
            ->assertSee('id="modal-edit-next-btn"', false)
            ->assertSee('Enregistrer et modifier la suivante');
    }
}
