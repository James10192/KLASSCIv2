<?php

namespace Tests\Feature\Care;

use App\Domain\Support\Models\SupportOutbox;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Signaler depuis l'ecole, puis suivre : le parcours cote instance, Master simule.
 */
class DemandeSupportTest extends TestCase
{
    use RefreshDatabase;

    private const CLE = '3f2b8c1e-5d4a-4f6b-9a7c-1e2d3c4b5a69';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([EnsureInstalled::class, CheckInstalled::class, PaywallMiddleware::class]);
        Cache::flush();
        config()->set('services.master.api_url', 'https://master.test/api');
        config()->set('services.master.support_token', 'kc_abcdefghijkl_'.str_repeat('A', 40));
    }

    private function utilisateur(): User
    {
        return User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8)), 'name' => 'Awa Koné']);
    }

    /** Le Master, simule : fonctionnalites ouvertes, puis la reponse voulue sur les demandes. */
    private function master($tickets, array $fonctionnalites = ['support_widget' => true, 'support_customer_portal' => true], array $portees = ['support:create', 'support:read', 'support:update']): void
    {
        Http::fake([
            'master.test/api/v1/support/bootstrap' => Http::response(['fonctionnalites' => $fonctionnalites, 'portees' => $portees]),
            'master.test/api/v1/support/tickets*' => $tickets,
        ]);
    }

    private function soumission(array $surcharge = []): array
    {
        return $surcharge + [
            'categorie' => 'PROBLEME',
            'description' => 'Le bouton Valider les notes ne répond plus.',
            'cle' => self::CLE,
            'contexte' => ['route_name' => 'support.demandes.index', 'url_path' => '/esbtp/notes?x=1', 'viewport' => '390x844'],
        ];
    }

    /** @test */
    public function le_rapporteur_est_l_utilisateur_connecte_jamais_le_navigateur(): void
    {
        $this->master(Http::response(['reference' => 'KC-2026-000042', 'statut' => ['code' => 'RECU', 'libelle' => 'Reçue']], 201));
        $user = $this->utilisateur();

        $this->actingAs($user)
            ->postJson(route('support.demandes.store'), $this->soumission(['reporter' => ['external_id' => 1]]))
            ->assertCreated()
            ->assertJsonPath('reference', 'KC-2026-000042')
            ->assertJsonPath('suivi_url', route('support.demandes.show', 'KC-2026-000042'));

        Http::assertSent(function (Request $req) use ($user) {
            return str_ends_with($req->url(), '/v1/support/tickets')
                && $req['reporter']['external_id'] === $user->id
                && $req['reporter']['name'] === 'Awa Koné'
                && $req['context']['url_path'] === '/esbtp/notes'
                && $req->hasHeader('Idempotency-Key', self::CLE)
                && $req->hasHeader('X-Request-ID');
        });
    }

    /** @test */
    public function master_injoignable_la_demande_attend_puis_part_avec_la_meme_cle(): void
    {
        $this->master(Http::sequence()->push([], 503)->push(['reference' => 'KC-2026-000043'], 201));
        $user = $this->utilisateur();

        $this->actingAs($user)->postJson(route('support.demandes.store'), $this->soumission())
            ->assertStatus(202)->assertJsonPath('en_attente', true);

        $ligne = SupportOutbox::firstOrFail();
        $this->assertSame(self::CLE, $ligne->idempotency_key);
        $this->assertSame($user->id, $ligne->user_id);

        // Le Master revient, le coupe-circuit retombe.
        Cache::forget('care:master:indisponible');
        $this->artisan('support:vider-boite-envoi')->assertSuccessful();

        $this->assertNotNull($ligne->fresh()->sent_at);
        $this->assertSame('KC-2026-000043', $ligne->fresh()->reference);
        Http::assertSent(fn (Request $req) => $req->hasHeader('Idempotency-Key', self::CLE));
    }

    /** @test */
    public function un_refus_du_master_abandonne_la_ligne_au_lieu_de_reessayer_sans_fin(): void
    {
        SupportOutbox::create(['idempotency_key' => self::CLE, 'payload' => ['report' => []]]);
        Http::fake(['master.test/*' => Http::response(['error' => 'validation_failed', 'message' => 'Non.'], 422)]);

        $this->artisan('support:vider-boite-envoi')->assertSuccessful();

        $this->assertNotNull(SupportOutbox::firstOrFail()->abandoned_at);
    }

    /** @test */
    public function un_identifiant_d_instance_refuse_ne_consomme_pas_d_essai(): void
    {
        $ligne = SupportOutbox::create(['idempotency_key' => self::CLE, 'payload' => ['report' => []]]);
        Http::fake(['master.test/*' => Http::response(['error' => 'invalid_credential'], 401)]);

        $this->artisan('support:vider-boite-envoi')->assertSuccessful();

        $this->assertSame(0, (int) $ligne->fresh()->attempts, "la faute est celle de l'instance, pas de la demande");
        $this->assertNull($ligne->fresh()->abandoned_at);
    }

    /** @test */
    public function un_texte_corrige_pendant_la_panne_remplace_celui_qui_attendait(): void
    {
        $this->master(Http::response([], 503));
        $user = $this->utilisateur();

        $this->actingAs($user)->postJson(route('support.demandes.store'), $this->soumission())->assertStatus(202);
        Cache::forget('care:master:indisponible');
        $this->actingAs($user)->postJson(route('support.demandes.store'), $this->soumission(['description' => 'Texte corrigé : le bouton Valider reste gris.']))
            ->assertStatus(202);

        $this->assertSame(1, SupportOutbox::count());
        $this->assertSame('Texte corrigé : le bouton Valider reste gris.', SupportOutbox::firstOrFail()->payload['report']['description']);
    }

    /** @test */
    public function la_boite_d_envoi_n_avance_pas_l_abandon_quand_le_coupe_circuit_est_ouvert(): void
    {
        $ligne = SupportOutbox::create(['idempotency_key' => self::CLE, 'payload' => ['report' => []]]);
        Cache::put('care:master:indisponible', 'http_503', 60);
        Http::fake();

        $this->artisan('support:vider-boite-envoi')->assertSuccessful();

        $this->assertSame(0, (int) $ligne->fresh()->attempts);
        Http::assertNothingSent();
    }

    /** @test */
    public function un_texte_modifie_depuis_un_envoi_recu_demande_une_cle_neuve(): void
    {
        $this->master(Http::response(['error' => 'idempotency_key_reused', 'message' => 'Autre contenu.'], 422));

        $this->actingAs($this->utilisateur())->postJson(route('support.demandes.store'), $this->soumission())
            ->assertStatus(409)->assertJsonPath('erreur', 'cle_perimee');
    }

    /** @test */
    public function la_page_montre_ce_qui_n_a_jamais_ete_transmis(): void
    {
        $this->master(Http::response(['data' => [], 'meta' => ['page' => 1, 'pages' => 1, 'total' => 0]]));
        $user = $this->utilisateur();
        SupportOutbox::create(['user_id' => $user->id, 'idempotency_key' => self::CLE,
            'payload' => ['report' => ['description' => 'Le relevé de notes est vide.']]])
            ->forceFill(['abandoned_at' => now()])->save();

        $this->actingAs($user)->get(route('support.demandes.index'))
            ->assertOk()->assertSee('Non transmise')->assertSee('Le relevé de notes est vide.');
    }

    /** @test */
    public function ferme_tant_que_le_master_ne_l_a_pas_ouvert_a_l_ecole(): void
    {
        $this->master(Http::response([], 201), ['support_widget' => false, 'support_customer_portal' => false]);

        $this->actingAs($this->utilisateur())->postJson(route('support.demandes.store'), $this->soumission())->assertNotFound();
        $this->actingAs($this->utilisateur())->get(route('support.demandes.index'))->assertNotFound();
    }

    /** @test */
    public function l_interrupteur_local_coupe_tout(): void
    {
        $this->master(Http::response([], 201));
        \App\Helpers\SettingsHelper::setOrCreate('support.widget.enabled', '0', 'support', 'boolean');
        Cache::flush();

        $this->actingAs($this->utilisateur())->postJson(route('support.demandes.store'), $this->soumission())->assertNotFound();
    }

    /** @test */
    public function la_soumission_est_validee(): void
    {
        $this->master(Http::response([], 201));

        $this->actingAs($this->utilisateur())
            ->postJson(route('support.demandes.store'), $this->soumission(['description' => 'court', 'cle' => 'pas-une-uuid']))
            ->assertStatus(422)->assertJsonValidationErrors(['description', 'cle']);
        Http::assertNotSent(fn (Request $req) => str_contains($req->url(), '/tickets'));
    }

    /** @test */
    public function il_faut_etre_connecte(): void
    {
        $this->postJson(route('support.demandes.store'), $this->soumission())->assertUnauthorized();
    }

    /** @test */
    public function la_portee_ecole_exige_la_permission(): void
    {
        $this->master(Http::response(['data' => [], 'meta' => ['page' => 1, 'pages' => 1, 'total' => 0]]));
        $user = $this->utilisateur();

        $this->actingAs($user)->get(route('support.demandes.index', ['portee' => 'ecole']))->assertOk();
        Http::assertSent(fn (Request $req) => str_contains($req->url(), 'scope=mine'));

        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'support.tickets.view_school', 'guard_name' => 'web']));
        $this->actingAs($user->fresh())->getJson(route('support.demandes.index', ['portee' => 'ecole']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('portee', 'school');
        Http::assertSent(fn (Request $req) => str_contains($req->url(), 'scope=school'));
    }

    /** @test */
    public function la_page_de_suivi_affiche_la_demande_du_master(): void
    {
        $this->master(Http::response([
            'reference' => 'KC-2026-000042', 'titre' => 'Le bouton Valider ne répond plus',
            'description' => 'Le bouton Valider les notes ne répond plus.',
            'categorie' => ['code' => 'PROBLEME', 'libelle' => 'Quelque chose ne fonctionne pas'],
            'statut' => ['code' => 'ACTION_REQUISE', 'libelle' => 'Action requise de votre part'],
            'rapporteur' => ['id' => 1, 'nom' => 'Awa Koné'],
            'cree_le' => now()->toIso8601String(), 'mis_a_jour_le' => now()->toIso8601String(),
            'derniere_reponse' => null,
            'messages' => [['auteur' => 'SUPPORT', 'nom' => 'Support KLASSCI', 'corps' => 'Pouvez-vous préciser la classe ?', 'le' => now()->toIso8601String()]],
        ]));

        $this->actingAs($this->utilisateur())->get(route('support.demandes.show', 'KC-2026-000042'))
            ->assertOk()
            ->assertSee('Action requise de votre part')
            ->assertSee('Pouvez-vous préciser la classe ?');
    }

    /** @test */
    public function une_reference_mal_formee_ne_part_pas_au_master(): void
    {
        $this->master(Http::response([], 200));

        $this->actingAs($this->utilisateur())->get('/support/demandes/..%2Fbootstrap')->assertNotFound();
        Http::assertNotSent(fn (Request $req) => str_contains($req->url(), '/tickets/'));
    }

    /** @test */
    public function la_fenetre_est_rendue_sans_jamais_exposer_l_identifiant_du_master(): void
    {
        $this->master(Http::response([], 201));
        $this->actingAs($this->utilisateur());

        $html = \Illuminate\Support\Facades\Blade::render('<x-support.lanceur />');

        $this->assertStringContainsString('id="sp-modal"', $html);
        $this->assertStringContainsString('window.KLASSCI_SUPPORT', $html);
        $this->assertStringNotContainsString('kc_abcdefghijkl', $html);
        $this->assertStringNotContainsString('master.test', $html);
    }

    /** @test */
    public function la_fenetre_n_est_pas_rendue_sans_identifiant_du_master(): void
    {
        config()->set('services.master.support_token', null);
        Http::fake();
        $this->actingAs($this->utilisateur());

        $this->assertStringNotContainsString('sp-modal', \Illuminate\Support\Facades\Blade::render('<x-support.lanceur />'));
        Http::assertNothingSent();
    }

    /** @test */
    public function chaque_reponse_porte_un_code_de_suivi(): void
    {
        $this->master(Http::response(['data' => [], 'meta' => ['page' => 1, 'pages' => 1, 'total' => 0]]));

        $r = $this->actingAs($this->utilisateur())->get(route('support.demandes.index'));
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', (string) $r->headers->get('X-Request-ID'));

        $repris = $this->actingAs($this->utilisateur())
            ->get(route('support.demandes.index'), ['X-Request-ID' => '01J8ZQ4Y5K3M2N1P0QRSTVWXYZ']);
        $this->assertSame('01J8ZQ4Y5K3M2N1P0QRSTVWXYZ', $repris->headers->get('X-Request-ID'));
    }

    private function detail(string $statut = 'ACTION_REQUISE', array $messages = [], int $rapporteurId = 1): array
    {
        return [
            'reference' => 'KC-2026-000042', 'titre' => 'Le bouton Valider ne répond plus',
            'description' => 'Le bouton Valider les notes ne répond plus.',
            'categorie' => ['code' => 'PROBLEME', 'libelle' => 'Quelque chose ne fonctionne pas'],
            'statut' => ['code' => $statut, 'libelle' => $statut],
            'rapporteur' => ['id' => $rapporteurId, 'nom' => 'Awa Koné'],
            'cree_le' => now()->toIso8601String(), 'mis_a_jour_le' => now()->toIso8601String(),
            'derniere_reponse' => null,
            'messages' => $messages,
        ];
    }

    /** @test */
    public function la_page_propose_de_repondre_seulement_a_l_auteur_si_l_identifiant_le_permet_et_la_demande_est_ouverte(): void
    {
        $user = $this->utilisateur();

        $this->master(Http::response($this->detail(rapporteurId: $user->id)));
        $this->actingAs($user)->get(route('support.demandes.show', 'KC-2026-000042'))->assertSee('id="sd-reponse"', false);

        // Http::fake empile ses reponses : on repart d'un client neuf pour chaque cas.
        Cache::flush();
        Http::swap(new \Illuminate\Http\Client\Factory());
        $this->master(Http::response($this->detail(rapporteurId: $user->id)), portees: ['support:create', 'support:read']);
        $this->actingAs($user)->get(route('support.demandes.show', 'KC-2026-000042'))->assertDontSee('id="sd-reponse"', false);

        Cache::flush();
        Http::swap(new \Illuminate\Http\Client\Factory());
        $this->master(Http::response($this->detail('FERME', rapporteurId: $user->id)));
        $this->actingAs($user)->get(route('support.demandes.show', 'KC-2026-000042'))->assertDontSee('id="sd-reponse"', false);

        // Lire les demandes de l'ecole n'autorise pas a ecrire sur celle d'un collegue.
        Cache::flush();
        Http::swap(new \Illuminate\Http\Client\Factory());
        $this->master(Http::response($this->detail(rapporteurId: $user->id + 1000)));
        $this->actingAs($user)->get(route('support.demandes.show', 'KC-2026-000042'))->assertDontSee('id="sd-reponse"', false);
    }

    /** @test */
    public function la_reponse_part_au_master_au_nom_de_l_utilisateur_connecte_avec_la_cle_du_brouillon(): void
    {
        $user = $this->utilisateur();
        $this->master(Http::response($this->detail('EN_ANALYSE', [
            ['auteur' => 'ECOLE', 'nom' => 'Awa Koné', 'corps' => 'La classe 2A BTS.', 'le' => now()->toIso8601String()],
        ], $user->id), 201));

        $this->actingAs($user)->postJson(route('support.demandes.repondre', 'KC-2026-000042'), ['corps' => 'La classe 2A BTS.', 'cle' => self::CLE])
            ->assertOk()
            ->assertJsonPath('peut_repondre', true)
            ->assertSee('La classe 2A BTS.', false);

        Http::assertSent(function (Request $req) use ($user) {
            return $req->method() === 'POST'
                && str_contains($req->url(), '/tickets/KC-2026-000042/messages')
                && str_contains($req->url(), 'reporter='.$user->getKey())
                && str_contains($req->url(), 'scope=mine')
                && $req->header('Idempotency-Key') === [self::CLE]
                && $req['author_name'] === 'Awa Koné'
                && $req['body'] === 'La classe 2A BTS.';
        });
    }

    /** @test */
    public function une_demande_fermee_refuse_la_reponse_et_rend_le_statut_a_jour(): void
    {
        $user = $this->utilisateur();
        Http::fake([
            'master.test/api/v1/support/bootstrap' => Http::response(['fonctionnalites' => ['support_widget' => true, 'support_customer_portal' => true], 'portees' => ['support:create', 'support:read', 'support:update']]),
            'master.test/api/v1/support/tickets/*/messages*' => Http::response(['error' => 'ticket_closed', 'message' => 'Fermée.'], 409),
            'master.test/api/v1/support/tickets/*' => Http::response($this->detail('FERME', rapporteurId: $user->id)),
        ]);

        $this->actingAs($user)->postJson(route('support.demandes.repondre', 'KC-2026-000042'), ['corps' => 'Toujours là ?', 'cle' => self::CLE])
            ->assertStatus(409)
            ->assertJsonPath('peut_repondre', false)
            ->assertJsonPath('statut', fn ($statut) => is_string($statut) && str_contains($statut, 'FERME'));
    }

    /** @test */
    public function une_demande_fermee_sans_relecture_possible_refuse_tout_de_meme_la_reponse(): void
    {
        $this->master(Http::response(['error' => 'ticket_closed', 'message' => 'Fermée.'], 409));

        $this->actingAs($this->utilisateur())->postJson(route('support.demandes.repondre', 'KC-2026-000042'), ['corps' => 'Toujours là ?', 'cle' => self::CLE])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Cette demande est fermée : ouvrez-en une nouvelle si le problème revient.')
            ->assertJsonPath('peut_repondre', false);
    }

    /** @test */
    public function master_injoignable_la_reponse_reste_dans_le_formulaire(): void
    {
        $this->master(Http::response([], 503));

        $this->actingAs($this->utilisateur())->postJson(route('support.demandes.repondre', 'KC-2026-000042'), ['corps' => 'Toujours là ?', 'cle' => self::CLE])
            ->assertStatus(503);
    }

    /** @test */
    public function une_portee_absente_retire_le_formulaire_sans_ouvrir_le_coupe_circuit(): void
    {
        $this->master(Http::response(['error' => 'insufficient_scope', 'message' => 'Portée requise : support:update.'], 403));

        $this->actingAs($this->utilisateur())->postJson(route('support.demandes.repondre', 'KC-2026-000042'), ['corps' => 'Toujours là ?', 'cle' => self::CLE])
            ->assertStatus(403)
            ->assertJsonPath('peut_repondre', false);

        $this->assertFalse(app(\App\Services\Care\ClientMasterSupport::class)->coupeCircuitOuvert());
    }

    /** @test */
    public function une_portee_absente_met_le_signalement_en_attente_au_lieu_de_le_perdre(): void
    {
        $this->master(Http::response(['error' => 'insufficient_scope', 'message' => 'Portée requise : support:create.'], 403));

        $this->actingAs($this->utilisateur())->postJson(route('support.demandes.store'), $this->soumission())
            ->assertStatus(202)->assertJsonPath('en_attente', true);

        $this->assertSame(1, SupportOutbox::count());
    }

    /** @test */
    public function une_portee_absente_n_abandonne_pas_la_boite_d_envoi(): void
    {
        SupportOutbox::create(['idempotency_key' => self::CLE, 'payload' => ['report' => []]]);
        SupportOutbox::create(['idempotency_key' => '9b2c7f1e-0d4a-4e8b-9c3f-2a1b0c9d8e7f', 'payload' => ['report' => []]]);
        Http::fake(['master.test/*' => Http::response(['error' => 'insufficient_scope', 'message' => 'Portée requise.'], 403)]);

        $this->artisan('support:vider-boite-envoi')->assertSuccessful();

        $this->assertSame(0, SupportOutbox::whereNotNull('abandoned_at')->count());
        $this->assertSame(0, (int) SupportOutbox::sum('attempts'));
        Http::assertSentCount(1);
    }

    /** @test */
    public function une_reponse_vide_ou_sans_cle_ne_part_pas(): void
    {
        $this->master(Http::response([], 200));

        $this->actingAs($this->utilisateur())->postJson(route('support.demandes.repondre', 'KC-2026-000042'), ['corps' => '', 'cle' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors(['corps', 'cle']);
        Http::assertNotSent(fn (Request $req) => str_contains($req->url(), '/messages'));
    }
}
