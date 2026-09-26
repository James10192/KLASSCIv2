<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Consommation\LigneDeConsommation;
use App\Domain\Assistant\Retours\RetourDeReponse;
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
 * 👍 / 👎 sur une réponse, et « Signaler à KLASSCI Care ».
 */
class RetoursTest extends TestCase
{
    use RefreshDatabase;

    private const CLE = '3f2b8c1e-5d4a-4f6b-9a7c-1e2d3c4b5a69';

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
            'services.master.support_token' => 'kc_abcdefghijkl_' . str_repeat('A', 40),
        ]);

        $this->user = User::factory()->create(['username' => 'u_' . Str::lower(Str::random(8))]);
        $this->conversation = ChatbotConversation::create([
            'user_id' => $this->user->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
        ]);
        ChatbotMessage::create(['conversation_id' => $this->conversation->id, 'role' => 'user', 'content' => 'Combien d\'inscrits ?']);
        $this->reponse = ChatbotMessage::create(['conversation_id' => $this->conversation->id, 'role' => 'assistant', 'content' => 'Il y a 214 inscrits.']);
        LigneDeConsommation::create([
            'message_id' => $this->reponse->id, 'conversation_id' => $this->conversation->id, 'fonction' => 'question',
            'modele' => 'or-gemini-flash-lite', 'fournisseur' => 'openrouter', 'identifiant_modele' => 'x', 'palier' => 'economique',
            'cout_fcfa' => 1, 'taux_usd_fcfa' => 600,
        ]);
    }

    public function test_un_pouce_en_bas_est_garde_avec_le_modele_et_fait_monter_la_conversation(): void
    {
        $this->actingAs($this->user)->postJson(route('chatbot.messages.retour', $this->reponse), [
            'avis' => 'pas_utile', 'raison' => 'faux', 'commentaire' => 'Il y en a 220.',
        ])->assertOk()->assertJson(['avis' => 'pas_utile', 'raison' => 'faux']);

        $retour = RetourDeReponse::sole();
        $this->assertSame('or-gemini-flash-lite', $retour->modele);
        $this->assertSame('economique', $retour->palier);
        $this->assertSame('standard', $this->conversation->fresh()->context['palier']);

        // Redonner le même avis ne remonte pas encore d'un cran ; changer d'avis ne crée pas de doublon.
        $this->actingAs($this->user)->postJson(route('chatbot.messages.retour', $this->reponse), ['avis' => 'pas_utile', 'raison' => 'faux'])->assertOk();
        $this->assertSame('standard', $this->conversation->fresh()->context['palier']);
        $this->actingAs($this->user)->postJson(route('chatbot.messages.retour', $this->reponse), ['avis' => 'utile'])->assertOk();
        $this->assertSame(1, RetourDeReponse::count());
        $this->assertNull(RetourDeReponse::sole()->raison);

        // L'historique rouvert montre l'avis donné.
        $historique = $this->actingAs($this->user)->getJson(route('chatbot.history', $this->conversation->session_id))->json('messages');
        $this->assertSame('utile', collect($historique)->firstWhere('id', $this->reponse->id)['retour']['avis']);
    }

    public function test_seule_la_personne_de_la_conversation_donne_un_avis_et_sur_une_reponse(): void
    {
        $autre = User::factory()->create(['username' => 'u_' . Str::lower(Str::random(8))]);
        $this->actingAs($autre)->postJson(route('chatbot.messages.retour', $this->reponse), ['avis' => 'utile'])->assertNotFound();

        $question = ChatbotMessage::where('role', 'user')->sole();
        $this->actingAs($this->user)->postJson(route('chatbot.messages.retour', $question), ['avis' => 'utile'])->assertNotFound();
        $this->actingAs($this->user)->postJson(route('chatbot.messages.retour', $this->reponse), ['avis' => 'bof'])->assertStatus(422);
        $this->assertSame(0, RetourDeReponse::count());
    }

    public function test_signaler_a_klassci_care_envoie_le_texte_relu_et_des_reperes_sans_donnee_d_eleve(): void
    {
        Http::fake([
            'master.test/api/v1/support/bootstrap' => Http::response(['fonctionnalites' => ['support_widget' => true, 'support_customer_portal' => true], 'portees' => ['support:create', 'support:read']]),
            'master.test/api/v1/support/tickets*' => Http::response(['reference' => 'KC-2026-000077', 'statut' => ['code' => 'RECU', 'libelle' => 'Reçue']], 201),
        ]);
        $this->actingAs($this->user)->postJson(route('chatbot.messages.retour', $this->reponse), ['avis' => 'pas_utile', 'raison' => 'faux'])->assertOk();

        $this->actingAs($this->user)->postJson(route('chatbot.messages.signaler', $this->reponse), [
            'description' => 'Le chiffre des inscrits est faux, le tableau de bord dit 220.',
            'cle' => self::CLE,
            'contexte' => ['url_path' => '/dashboard', 'extras' => ['composant' => 'autre chose']],
        ])->assertCreated()->assertJsonPath('reference', 'KC-2026-000077');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/support/tickets')
            && $r['report']['category'] === 'INFORMATION_INCORRECTE'
            && str_contains($r['report']['description'], 'le tableau de bord dit 220')
            && str_contains($r['report']['description'], 'conversation ' . $this->conversation->session_id)
            && str_contains($r['report']['description'], 'modèle or-gemini-flash-lite')
            && $r['context']['extras']['composant'] === 'assistant');

        $this->assertSame('KC-2026-000077', RetourDeReponse::sole()->care_reference);
    }

    public function test_sans_klassci_care_ouvert_le_signalement_est_introuvable(): void
    {
        Http::fake([
            'master.test/api/v1/support/bootstrap' => Http::response(['fonctionnalites' => ['support_widget' => false], 'portees' => []]),
        ]);

        $this->actingAs($this->user)->postJson(route('chatbot.messages.signaler', $this->reponse), [
            'description' => 'Le chiffre des inscrits est faux.', 'cle' => self::CLE,
        ])->assertNotFound();
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/tickets'));
    }
}
