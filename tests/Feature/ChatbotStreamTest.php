<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Models\ChatbotMessage;
use App\Models\User;
use App\Domain\Assistant\Flux\UiMessageStream;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Unit\Domain\Assistant\FluxEnregistres;

/**
 * Route /chatbot/message/stream : protocole UI message stream v1 et contrôle
 * des outils. L'API Anthropic est simulée (Http::fake), aucun appel réseau.
 */
class ChatbotStreamTest extends TestCase
{
    use RefreshDatabase;

    /** @var string[] */
    private array $frames = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([EnsureInstalled::class, CheckInstalled::class]);

        // Un seul modèle configuré : Claude, sur une URL de test. Les autres
        // fournisseurs n'ont pas de clé, donc aucun repli possible.
        config([
            'assistant.fournisseurs.anthropic.cle' => 'sk-ant-test',
            'assistant.fournisseurs.anthropic.url' => 'https://api.anthropic.test/v1/',
            'assistant.fournisseurs.openai.cle' => null,
            'assistant.fournisseurs.mistral.cle' => null,
            'assistant.fournisseurs.deepseek.cle' => null,
            'assistant.fournisseurs.openrouter.cle' => null,
            'assistant.fournisseurs.gemini.cle' => null,
            'assistant.modele_defaut' => 'claude-haiku',
            'assistant.modeles_autorises' => [],
        ]);

        $this->frames = [];
        $this->app->instance(UiMessageStream::class, new UiMessageStream(function (string $frame) {
            $this->frames[] = $frame;
        }));
    }

    private function user(array $permissions = []): User
    {
        $user = User::factory()->create(['username' => 'u_' . Str::lower(Str::random(8))]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    /** @return array<int, array> */
    private function parts(): array
    {
        return array_values(array_map(
            fn (string $frame) => json_decode(substr(trim($frame), strlen('data: ')), true),
            array_filter($this->frames, fn ($f) => trim($f) !== 'data: [DONE]')
        ));
    }

    private function sse(string $body)
    {
        return Http::response($body, 200, ['Content-Type' => 'text/event-stream']);
    }

    public function test_le_flux_parle_le_protocole_ai_sdk_et_enregistre_la_reponse(): void
    {
        Http::fake([
            'api.anthropic.test/*' => Http::sequence()
                ->push(FluxEnregistres::anthropicTexte(), 200, ['Content-Type' => 'text/event-stream'])
                ->push(FluxEnregistres::anthropicTexte('Point du jour'), 200, ['Content-Type' => 'text/event-stream']),
        ]);

        $user = $this->user();

        $response = $this->actingAs($user)->post(route('chatbot.message.stream'), ['message' => 'Bonjour']);

        $response->assertOk();
        $response->assertHeader('x-vercel-ai-ui-message-stream', 'v1');
        $response->assertHeader('X-Accel-Buffering', 'no');
        $this->assertStringStartsWith('text/event-stream', $response->headers->get('Content-Type'));

        $response->streamedContent();

        $types = array_column($this->parts(), 'type');
        $this->assertSame(
            ['start', 'start-step', 'text-start', 'text-delta', 'text-end', 'finish-step', 'message-metadata', 'finish'],
            $types
        );
        $this->assertSame("data: [DONE]\n\n", end($this->frames));

        $parts = $this->parts();
        $conversationId = $parts[0]['messageMetadata']['conversationId'];
        $this->assertSame('Voici les résultats.', $parts[3]['delta']);
        $this->assertSame($conversationId, $parts[6]['messageMetadata']['conversationId']);

        $saved = ChatbotMessage::where('role', 'assistant')->latest('id')->first();
        $this->assertSame('Voici les résultats.', $saved->content);
        $this->assertSame(1, ChatbotMessage::where('role', 'user')->where('content', 'Bonjour')->count());

        // Le premier appel est diffusé, le second (titre) ne l'est pas.
        Http::assertSent(fn (HttpRequest $request) => ($request->data()['stream'] ?? false) === true);
    }

    public function test_un_outil_non_autorise_n_est_ni_declare_ni_execute(): void
    {
        $toolUse = str_replace('Je cherche ', 'Je regarde ', FluxEnregistres::anthropicTexteEtOutil());

        Http::fake([
            'api.anthropic.test/*' => Http::sequence()
                ->push($toolUse, 200, ['Content-Type' => 'text/event-stream'])
                ->push(FluxEnregistres::anthropicTexte(), 200, ['Content-Type' => 'text/event-stream'])
                ->push(FluxEnregistres::anthropicTexte('Inscriptions'), 200, ['Content-Type' => 'text/event-stream']),
        ]);

        // dashboard.view ouvre navigate_to_page, mais PAS search_inscriptions (inscriptions.view).
        $user = $this->user(['dashboard.view']);

        $this->actingAs($user)
            ->post(route('chatbot.message.stream'), ['message' => 'Montre les inscriptions'])
            ->assertOk()
            ->streamedContent();

        $recorded = Http::recorded();
        $premier = $recorded[0][0]->data();
        $declares = array_column($premier['tools'] ?? [], 'name');
        $this->assertContains('navigate_to_page', $declares);
        $this->assertNotContains('search_inscriptions', $declares);

        // Le modèle demande quand même l'outil : le serveur refuse, sans rien exécuter.
        $second = $recorded[1][0]->data();
        $toolResult = collect(end($second['messages'])['content'])->firstWhere('type', 'tool_result');
        // L'identifiant renvoyé à Anthropic est celui de la boucle, cohérent avec son tool_use.
        $this->assertSame('a00000001', $toolResult['tool_use_id']);
        $toolUse = collect($second['messages'][count($second['messages']) - 2]['content'])->firstWhere('type', 'tool_use');
        $this->assertSame('a00000001', $toolUse['id']);
        $this->assertSame(['error' => 'Outil indisponible.'], json_decode($toolResult['content'], true));

        $outil = collect($this->parts())->where('type', 'data-etape')->last();
        $this->assertSame('echec', $outil['data']['etat']);
        $this->assertSame('a00000001', $outil['id']);

        // Aucune donnée d'inscription n'est partie au navigateur.
        $this->assertEmpty(collect($this->parts())->filter(fn ($p) => in_array($p['type'], ['data-table', 'data-cards', 'data-widget'], true)));
        $this->assertStringNotContainsString('KONAN', implode('', $this->frames));

        $assistant = ChatbotMessage::where('role', 'assistant')->latest('id')->first();
        $this->assertSame([], $assistant->metadata['tool_calls']);
    }

    public function test_une_erreur_d_api_donne_une_partie_error_sans_detail_interne(): void
    {
        Http::fake([
            'api.anthropic.test/*' => Http::response(['error' => ['type' => 'overloaded_error', 'message' => 'secret interne']], 529),
        ]);

        $user = $this->user();

        $this->actingAs($user)
            ->post(route('chatbot.message.stream'), ['message' => 'Bonjour'])
            ->assertOk()
            ->streamedContent();

        $error = collect($this->parts())->firstWhere('type', 'error');
        $this->assertNotNull($error);
        $this->assertStringNotContainsString('secret', $error['errorText']);
        $this->assertNotContains('finish', array_column($this->parts(), 'type'));
        $this->assertSame("data: [DONE]\n\n", end($this->frames));
    }

    public function test_choisir_son_modele_demande_la_permission(): void
    {
        Http::fake();

        $this->actingAs($this->user())
            ->postJson(route('chatbot.message.stream'), ['message' => 'Bonjour', 'modele' => 'claude-haiku'])
            ->assertStatus(403);

        $this->actingAs($this->user(['assistant.model.choose']))
            ->postJson(route('chatbot.message.stream'), ['message' => 'Bonjour', 'modele' => 'gpt-4o-mini'])
            ->assertStatus(422); // déclaré, mais sans clé : pas disponible

        Http::assertNothingSent();
    }

    public function test_message_trop_long_refuse_avant_toute_diffusion(): void
    {
        Http::fake();

        $this->actingAs($this->user())
            ->postJson(route('chatbot.message.stream'), ['message' => str_repeat('a', 1001)])
            ->assertStatus(422);

        Http::assertNothingSent();
    }
}
