<?php

namespace Tests\Feature\Care;

use App\Domain\Assistant\Consommation\LigneDeConsommation;
use App\Domain\Assistant\Retours\RetourDeReponse;
use App\Domain\Support\Models\SupportOutbox;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Les 👍 / 👎 sur les réponses de l'assistant partent au Master, par la boîte
 * d'envoi KLASSCI Care : jamais pendant la requête de la personne.
 */
class RetourAssistantVersMasterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ChatbotConversation $conversation;

    private ChatbotMessage $reponse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([EnsureInstalled::class, CheckInstalled::class, PaywallMiddleware::class]);
        Cache::flush();
        config([
            'assistant.paliers' => ['economique' => ['or-gemini-flash-lite'], 'standard' => ['or-gemini-flash'], 'avance' => ['claude-sonnet']],
            'services.master.api_url' => 'https://master.test/api',
            'services.master.support_token' => 'kc_abcdefghijkl_'.str_repeat('A', 40),
        ]);

        $this->user = User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8)), 'name' => 'Awa Koné']);
        $this->conversation = ChatbotConversation::create([
            'user_id' => $this->user->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
            'context' => ['last_page_path' => '/esbtp/inscriptions'],
        ]);
        ChatbotMessage::create(['conversation_id' => $this->conversation->id, 'role' => 'user', 'content' => "Combien d'inscrits ?"]);
        $this->reponse = ChatbotMessage::create(['conversation_id' => $this->conversation->id, 'role' => 'assistant', 'content' => 'Il y a 214 inscrits.']);
        LigneDeConsommation::create([
            'message_id' => $this->reponse->id, 'conversation_id' => $this->conversation->id, 'fonction' => 'question',
            'modele' => 'or-gemini-flash-lite', 'fournisseur' => 'openrouter', 'identifiant_modele' => 'x', 'palier' => 'economique',
            'cout_fcfa' => 1, 'taux_usd_fcfa' => 600,
        ]);
    }

    private function master(bool $ouvert = true, $retours = null): void
    {
        Http::fake([
            'master.test/api/v1/support/bootstrap' => Http::response(['fonctionnalites' => ['support_widget' => $ouvert], 'portees' => ['support:create']]),
            'master.test/api/v1/support/retours-assistant' => $retours ?? Http::response(['ok' => true], 201),
            'master.test/api/v1/support/tickets*' => Http::response(['reference' => 'KC-2026-000099'], 201),
        ]);
    }

    private function avis(array $donnees): void
    {
        $this->actingAs($this->user)->postJson(route('chatbot.messages.retour', $this->reponse), $donnees)->assertOk();
    }

    public function test_un_avis_part_par_la_boite_d_envoi_avec_le_contrat_exact(): void
    {
        $this->master();
        $this->avis(['avis' => 'pas_utile', 'raison' => 'faux', 'commentaire' => 'Il y en a 220.']);

        // Rien n'est parti pendant la requête de la personne.
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'retours-assistant'));
        $ligne = SupportOutbox::sole();
        $retour = RetourDeReponse::sole();
        $this->assertSame(SupportOutbox::AVIS_ASSISTANT, $ligne->kind);
        $this->assertSame($retour->care_uuid, $ligne->idempotency_key);

        $this->artisan('support:vider-boite-envoi')->assertSuccessful();

        $this->assertNotNull($ligne->fresh()->sent_at);
        Http::assertSent(function (Request $r) use ($retour) {
            if (! str_ends_with($r->url(), '/v1/support/retours-assistant')) {
                return false;
            }
            $corps = $r->data();

            return $r->method() === 'POST'
                && $r->hasHeader('Idempotency-Key', $retour->care_uuid)
                && $r->hasHeader('Authorization')
                && array_keys($corps) === ['avis', 'raison', 'commentaire', 'question', 'reponse', 'modele', 'page', 'utilisateur', 'conversation_ref', 'message_ref', 'donne_le']
                && $corps['avis'] === 'pas_utile'
                && $corps['raison'] === 'faux'
                && $corps['commentaire'] === 'Il y en a 220.'
                && $corps['question'] === "Combien d'inscrits ?"
                && $corps['reponse'] === 'Il y a 214 inscrits.'
                && $corps['modele'] === 'or-gemini-flash-lite'
                && $corps['page'] === '/esbtp/inscriptions'
                && $corps['utilisateur'] === ['id' => $this->user->id, 'nom' => 'Awa Koné', 'role' => null]
                && $corps['conversation_ref'] === $this->conversation->session_id
                && $corps['message_ref'] === (string) $this->reponse->id;
        });
    }

    public function test_changer_d_avis_avant_l_envoi_remplace_apres_l_envoi_cree_une_nouvelle_cle(): void
    {
        $this->master();
        $this->avis(['avis' => 'utile']);
        $this->avis(['avis' => 'pas_utile', 'raison' => 'incomplet']);

        $this->assertSame(1, SupportOutbox::count());
        $this->assertSame('pas_utile', SupportOutbox::sole()->payload['avis']);

        $this->artisan('support:vider-boite-envoi')->assertSuccessful();
        $uuid = RetourDeReponse::sole()->care_uuid;

        // Même avis redonné : rien ne repart.
        $this->avis(['avis' => 'pas_utile', 'raison' => 'incomplet']);
        $this->assertSame(1, SupportOutbox::count());

        $this->avis(['avis' => 'utile']);
        $this->assertSame([$uuid, $uuid.':2'], SupportOutbox::orderBy('id')->pluck('idempotency_key')->all());
    }

    public function test_support_ferme_aucun_avis_ne_part(): void
    {
        $this->master(ouvert: false);
        $this->avis(['avis' => 'utile']);

        $this->assertSame(0, SupportOutbox::count());
        $this->assertSame(1, RetourDeReponse::count());
    }

    public function test_route_des_avis_absente_au_master_differe_sans_bloquer_les_signalements(): void
    {
        $this->master(retours: Http::response(['error' => 'not_found'], 404));
        $this->avis(['avis' => 'utile']);
        $ticket = SupportOutbox::create(['idempotency_key' => '3f2b8c1e-5d4a-4f6b-9a7c-1e2d3c4b5a69', 'payload' => ['report' => []]]);

        $this->artisan('support:vider-boite-envoi')->assertSuccessful();

        $avis = SupportOutbox::where('kind', SupportOutbox::AVIS_ASSISTANT)->sole();
        $this->assertNull($avis->sent_at);
        $this->assertNull($avis->abandoned_at);
        $this->assertSame(0, (int) $avis->attempts);
        $this->assertTrue($avis->next_attempt_at->isFuture());
        $this->assertSame('KC-2026-000099', $ticket->fresh()->reference);
    }

    public function test_master_injoignable_l_avis_est_enregistre_et_la_personne_n_attend_pas(): void
    {
        Http::fake([
            'master.test/api/v1/support/bootstrap' => Http::response(['fonctionnalites' => ['support_widget' => true]]),
            'master.test/*' => Http::response([], 503),
        ]);
        $this->avis(['avis' => 'utile']);

        $this->artisan('support:vider-boite-envoi')->assertSuccessful();

        $ligne = SupportOutbox::sole();
        $this->assertNull($ligne->sent_at);
        $this->assertSame(1, (int) $ligne->attempts);
    }

    public function test_mes_demandes_ne_montre_pas_les_avis(): void
    {
        $this->master();
        $this->avis(['avis' => 'utile']);

        $this->assertSame(0, SupportOutbox::signalements()->count());
    }
    public function test_une_reponse_reduite_a_une_carte_part_avec_un_repli_et_non_vide(): void
    {
        $this->master();
        ChatbotMessage::where('conversation_id', $this->conversation->id)->where('role', 'user')->delete();
        $this->reponse->forceFill(['content' => '', 'display_data' => ['widget' => ['kind' => 'approbation', 'titre' => 'Nouvelle évaluation · 1BTS A']]])->save();

        $this->avis(['avis' => 'pas_utile', 'raison' => 'faux']);
        $this->artisan('support:vider-boite-envoi')->assertSuccessful();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/retours-assistant')
            && $r->data()['question'] === '[sans question]'
            && $r->data()['reponse'] === '[proposition : Nouvelle évaluation · 1BTS A]');
    }

    public function test_les_signalements_passent_avant_les_avis_et_les_avis_sont_plafonnes(): void
    {
        config(['support.boite_envoi.avis_par_passage' => 2]);
        $this->master();
        foreach (range(1, 3) as $i) {
            SupportOutbox::create(['kind' => SupportOutbox::AVIS_ASSISTANT, 'idempotency_key' => (string) Str::uuid(), 'payload' => ['avis' => 'utile']]);
        }
        $ticket = SupportOutbox::create(['idempotency_key' => (string) Str::uuid(), 'payload' => ['report' => []]]);

        $this->artisan('support:vider-boite-envoi')->assertSuccessful();

        $this->assertNotNull($ticket->fresh()->sent_at);
        $this->assertSame(2, SupportOutbox::where('kind', SupportOutbox::AVIS_ASSISTANT)->whereNotNull('sent_at')->count());
        $envois = collect(Http::recorded())->map(fn ($p) => $p[0]->url())->filter(fn ($u) => ! str_contains($u, 'bootstrap'))->values();
        $this->assertStringContainsString('/tickets', $envois->first(), 'Le signalement part le premier.');
    }

    public function test_une_ligne_reservee_par_un_envoi_n_est_pas_remplacee(): void
    {
        $this->master();
        $this->avis(['avis' => 'utile']);
        $this->assertTrue(SupportOutbox::sole()->reserver());

        $this->avis(['avis' => 'pas_utile', 'raison' => 'faux']);

        $lignes = SupportOutbox::orderBy('id')->get();
        $this->assertCount(2, $lignes, 'Une nouvelle version part au lieu d\'écraser la charge en route.');
        $this->assertSame('utile', $lignes[0]->payload['avis']);
        $this->assertSame('pas_utile', $lignes[1]->payload['avis']);
    }
}
