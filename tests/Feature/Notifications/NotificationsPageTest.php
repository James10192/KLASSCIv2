<?php

namespace Tests\Feature\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Page /notifications : tout se fait sans recharger. Les filtres, « Afficher
 * les plus anciennes », « Tout marquer comme lu » et la suppression passent
 * par des réponses JSON ; la page complète, elle, rend ses lignes groupées par
 * date et un état vide qui dit quoi faire.
 */
class NotificationsPageTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        $this->user = User::factory()->create(['is_active' => true]);
    }

    private function notif(array $attrs = []): Notification
    {
        $n = Notification::create(array_merge([
            'user_id' => $this->user->id,
            'title' => 'Paiement reçu',
            'message' => 'Un paiement a été enregistré. Statut: validé',
            'type' => 'success',
            'is_read' => false,
        ], $attrs));

        if (isset($attrs['created_at'])) {
            $n->forceFill(['created_at' => $attrs['created_at']])->save();
        }

        return $n;
    }

    public function test_la_page_rend_les_groupes_les_kpis_et_le_lien_d_ouverture(): void
    {
        $this->notif(['link' => '/esbtp/paiements/12']);
        $this->notif(['title' => 'Ancienne', 'is_read' => true, 'created_at' => now()->subMonth()]);

        $this->actingAs($this->user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('ntf-hero', false)
            ->assertSee("Aujourd'hui", false)
            ->assertSee('Plus ancien')
            ->assertSee('data-ntf-url="/esbtp/paiements/12"', false)
            ->assertSee('Tout marquer comme lu');
    }

    public function test_un_lien_externe_ou_javascript_n_est_jamais_propose(): void
    {
        $this->notif(['link' => 'javascript:alert(1)']);
        $this->notif(['link' => 'https://exemple.invalid/piege']);

        $this->actingAs($this->user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('javascript:alert', false)
            ->assertDontSee('exemple.invalid', false);
    }

    public function test_l_etat_vide_dit_que_tout_est_a_jour(): void
    {
        $this->actingAs($this->user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Vous êtes à jour');
    }

    public function test_le_fragment_filtre_les_non_lues_et_rend_les_compteurs(): void
    {
        $this->notif(['title' => 'Non lue A']);
        $this->notif(['title' => 'Lue B', 'is_read' => true]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index', ['fragment' => 1, 'filtre' => 'non_lues']))
            ->assertOk()
            ->assertJsonPath('counts.unread', 1)
            ->assertJsonPath('counts.total', 2)
            ->assertJsonPath('counts.today', 2)
            ->assertJsonPath('total', 1);

        $this->assertStringContainsString('Non lue A', $response->json('html'));
        $this->assertStringNotContainsString('Lue B', $response->json('html'));
    }

    public function test_le_fragment_filtre_par_type(): void
    {
        $this->notif(['title' => 'Erreur grave', 'type' => 'error']);
        $this->notif(['title' => 'Tout va bien', 'type' => 'success']);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index', ['fragment' => 1, 'type' => 'alerte']))
            ->assertOk()
            ->assertJsonPath('counts.types.alerte', 1);

        $this->assertStringContainsString('Erreur grave', $response->json('html'));
        $this->assertStringNotContainsString('Tout va bien', $response->json('html'));
    }

    public function test_charger_plus_donne_l_adresse_suivante_et_ne_repete_pas_le_titre_de_groupe(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->notif(['title' => "Notif {$i}"]);
        }

        $first = $this->actingAs($this->user)
            ->getJson(route('notifications.index', ['fragment' => 1]))
            ->assertOk();

        $next = $first->json('next_url');
        $this->assertNotNull($next);
        $this->assertStringContainsString('after_group=aujourdhui', $next);

        $second = $this->actingAs($this->user)->getJson($next)->assertOk();
        $this->assertStringNotContainsString('data-ntf-group', $second->json('html'));
        $this->assertNull($second->json('next_url'));
    }

    public function test_les_compteurs_seuls(): void
    {
        $this->notif();

        $this->actingAs($this->user)
            ->getJson(route('notifications.index', ['fragment' => 1, 'counts_only' => 1]))
            ->assertOk()
            ->assertJsonPath('counts.unread', 1)
            ->assertJsonMissingPath('html');
    }

    public function test_marquer_lue_tout_marquer_et_supprimer_repondent_en_json(): void
    {
        $a = $this->notif();
        $b = $this->notif();

        $this->actingAs($this->user)
            ->postJson(route('notifications.mark-as-read', ['id' => $a->id]))
            ->assertOk()->assertJson(['success' => true]);
        $this->assertTrue($a->fresh()->is_read);

        $this->actingAs($this->user)
            ->postJson(route('notifications.mark-all-as-read'))
            ->assertOk()->assertJson(['success' => true]);
        $this->assertTrue($b->fresh()->is_read);

        $this->actingAs($this->user)
            ->deleteJson(route('notifications.delete', ['id' => $b->id]))
            ->assertOk()->assertJson(['success' => true]);
        $this->assertNull(Notification::find($b->id));
    }

    public function test_on_ne_touche_pas_aux_notifications_d_un_autre(): void
    {
        $autre = User::factory()->create();
        $n = Notification::create([
            'user_id' => $autre->id, 'title' => 'Privée', 'message' => 'x', 'type' => 'info', 'is_read' => false,
        ]);

        $this->actingAs($this->user)
            ->deleteJson(route('notifications.delete', ['id' => $n->id]))
            ->assertNotFound();
        $this->assertNotNull($n->fresh());

        $this->actingAs($this->user)
            ->getJson(route('notifications.index', ['fragment' => 1]))
            ->assertJsonPath('counts.total', 0);
    }
    public function test_le_raccourci_coordinateur_lit_des_mots_entiers(): void
    {
        Permission::findOrCreate('identity.coordinate', 'web');
        $this->user->givePermissionTo('identity.coordinate');

        $this->notif(['title' => 'Rappel : conseil de classe', 'type' => 'info']);
        $this->notif(['title' => 'Appel clôturé en BTS 1', 'type' => 'info']);

        $html = $this->actingAs($this->user)
            ->getJson(route('notifications.index', ['fragment' => 1]))
            ->assertOk()
            ->json('html');

        // Un seul bouton « Voir les présences » : celui de l'appel, pas du rappel.
        $this->assertSame(1, substr_count($html, 'Voir les présences'));
    }
}
