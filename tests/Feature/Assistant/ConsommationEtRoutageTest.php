<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Consommation\BudgetAssistant;
use App\Domain\Assistant\Consommation\LigneDeConsommation;
use App\Domain\Assistant\Consommation\Tarifs;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\EvenementModele;
use App\Domain\Assistant\Fournisseurs\OpenAiCompatible;
use App\Domain\Assistant\Harnais\ResultatBoucle;
use App\Domain\Assistant\Routage\Routeur;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Domain\Assistant\FluxEnregistres;

/**
 * Coût de chaque échange, routage par palier et budget mensuel de l'école.
 */
class ConsommationEtRoutageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([EnsureInstalled::class, CheckInstalled::class]);

        config([
            'assistant.fournisseurs.openrouter.cle' => 'sk-or-test',
            'assistant.fournisseurs.openrouter.url' => 'https://openrouter.test/api/v1/',
            'assistant.fournisseurs.anthropic.cle' => 'sk-ant-test',
            'assistant.fournisseurs.anthropic.url' => 'https://api.anthropic.test/v1/',
            'assistant.fournisseurs.openai.cle' => null,
            'assistant.fournisseurs.mistral.cle' => null,
            'assistant.fournisseurs.deepseek.cle' => null,
            'assistant.fournisseurs.gemini.cle' => null,
            'assistant.modeles_autorises' => [],
            'assistant.paliers' => [
                'economique' => ['or-gemini-flash-lite', 'or-gpt-4o-mini'],
                'standard' => ['or-gemini-flash'],
                'avance' => ['claude-sonnet'],
            ],
            'assistant.budget.mensuel_fcfa' => 0,
            'assistant.budget.taux_usd_fcfa' => 600,
        ]);
    }

    private function user(): User
    {
        return User::factory()->create(['username' => 'u_' . Str::lower(Str::random(8))]);
    }

    private function cles(array $candidats): array
    {
        return array_map(fn ($m) => $m->cle, $candidats);
    }

    public function test_le_cout_reel_d_openrouter_est_repris_tel_quel(): void
    {
        $sse = [
            ['event' => null, 'data' => ['choices' => [['delta' => ['content' => 'Bonjour'], 'finish_reason' => null]]]],
            ['event' => null, 'data' => ['choices' => [['delta' => [], 'finish_reason' => 'stop']]]],
            ['event' => null, 'data' => ['choices' => [], 'usage' => ['prompt_tokens' => 1200, 'completion_tokens' => 80, 'cost' => 0.00042, 'prompt_tokens_details' => ['cached_tokens' => 1000]]]],
        ];

        $usage = collect(iterator_to_array((new OpenAiCompatible())->traduire($sse), false))
            ->firstWhere('type', EvenementModele::USAGE);

        $this->assertSame(['entree' => 1200, 'sortie' => 80, 'cache' => 1000, 'cout' => 0.00042], $usage->donnees);
    }

    public function test_sans_cout_du_fournisseur_le_tarif_declare_s_applique(): void
    {
        // 1 M de jetons d'entrée à 0,75 $ + 100 000 en sortie à 3,75 $ le million.
        $this->assertEqualsWithDelta(1.125, Tarifs::coutUsd('or-gemini-flash', 1_000_000, 100_000), 1e-9);
        // Jetons lus en cache : au tarif cache (0,075 $ le million).
        $this->assertEqualsWithDelta(0.075, Tarifs::coutUsd('or-gemini-flash', 1_000_000, 0, 1_000_000), 1e-9);
        $this->assertSame(675.0, Tarifs::enFcfa(1.125));
    }

    public function test_une_question_simple_part_au_palier_economique_une_analyse_au_standard(): void
    {
        $routeur = app(Routeur::class);

        $simple = $routeur->decider('Combien d\'inscrits cette année ?', null);
        $this->assertSame('economique', $simple->palier);
        // Les paliers au-dessus servent de repli, dans l'ordre.
        $this->assertSame(['or-gemini-flash-lite', 'or-gpt-4o-mini', 'or-gemini-flash', 'claude-sonnet'], $this->cles($simple->candidats));

        $analyse = $routeur->decider('Compare les encaissements de ce mois avec le mois dernier', null);
        $this->assertSame('standard', $analyse->palier);
        $this->assertSame(['or-gemini-flash', 'claude-sonnet'], $this->cles($analyse->candidats));
    }

    public function test_reessayer_monte_d_un_palier_et_la_conversation_garde_son_palier(): void
    {
        $routeur = app(Routeur::class);
        $this->assertSame('standard', $routeur->decider('Combien d\'inscrits ?', null, relance: true)->palier);

        $conversation = new ChatbotConversation(['context' => ['palier' => 'avance']]);
        $this->assertSame('avance', $routeur->decider('Combien d\'inscrits ?', $conversation)->palier);
    }

    public function test_un_echec_fait_monter_la_conversation_d_un_palier(): void
    {
        $routeur = app(Routeur::class);
        $decision = $routeur->decider('Combien d\'inscrits ?', null);

        $echec = new ResultatBoucle('erreur', '', '', [], 'or-gemini-flash-lite', 'openrouter', [], 0, 0, 1, 10);
        $bute = new ResultatBoucle('ok', '', '', [], 'or-gemini-flash-lite', 'openrouter', [], 0, 0, 2, 10, [], echecsOutils: 1);
        $reussi = new ResultatBoucle('ok', 'Il y a 214 inscrits.', 'Il y a 214 inscrits.', [], 'or-gemini-flash-lite', 'openrouter', [], 0, 0, 2, 10);

        $this->assertSame('standard', $routeur->palierApres($decision, $echec));
        $this->assertSame('standard', $routeur->palierApres($decision, $bute));
        $this->assertNull($routeur->palierApres($decision, $reussi));
    }

    public function test_budget_atteint_palier_economique_seulement_puis_pause(): void
    {
        config(['assistant.budget.mensuel_fcfa' => 1000]);
        LigneDeConsommation::create(['fonction' => 'question', 'modele' => 'x', 'fournisseur' => 'x', 'identifiant_modele' => 'x', 'cout_fcfa' => 1050, 'taux_usd_fcfa' => 600]);
        app(BudgetAssistant::class)->oublier();

        $decision = app(Routeur::class)->decider('Compare les deux classes', null);
        $this->assertSame('budget_atteint', $decision->raison);
        $this->assertSame(['or-gemini-flash-lite', 'or-gpt-4o-mini'], $this->cles($decision->candidats));
        // Sans montée : un échec ne fait pas changer de palier quand le budget est atteint.
        $this->assertNull(app(Routeur::class)->palierApres($decision, new ResultatBoucle('erreur', '', '', [], null, null, [], 0, 0, 1, 1)));

        LigneDeConsommation::create(['fonction' => 'question', 'modele' => 'x', 'fournisseur' => 'x', 'identifiant_modele' => 'x', 'cout_fcfa' => 200, 'taux_usd_fcfa' => 600]);
        app(BudgetAssistant::class)->oublier();
        $this->assertTrue(app(Routeur::class)->decider('Bonjour', null)->pause);
    }

    public function test_un_echange_enregistre_son_cout_rattache_au_message(): void
    {
        // Seul Claude Sonnet est joignable : l'échange y arrive par repli.
        config(['assistant.fournisseurs.openrouter.cle' => null]);
        Http::fake([
            'api.anthropic.test/*' => Http::sequence()
                ->push(FluxEnregistres::anthropicTexte(), 200, ['Content-Type' => 'text/event-stream'])
                ->push(FluxEnregistres::anthropicTexte('Inscriptions'), 200, ['Content-Type' => 'text/event-stream']),
        ]);
        $this->app->instance(UiMessageStream::class, new UiMessageStream(fn () => null));
        $user = $this->user();

        $this->actingAs($user)->post(route('chatbot.message.stream'), ['message' => 'Bonjour'])->assertOk()->streamedContent();

        $message = ChatbotMessage::where('role', 'assistant')->latest('id')->first();
        $ligne = LigneDeConsommation::where('fonction', 'question')->sole();
        $this->assertSame($message->id, $ligne->message_id);
        $this->assertSame($user->id, $ligne->user_id);
        $this->assertSame('claude-sonnet', $ligne->modele);
        $this->assertSame(30, $ligne->tokens_entree);
        $this->assertSame(6, $ligne->tokens_sortie);
        $this->assertFalse($ligne->cout_exact);
        // 30 × 3 $ + 6 × 15 $ le million = 0,00018 $ → 0,11 FCFA à 600.
        $this->assertEqualsWithDelta(0.00018, $ligne->cout_usd, 1e-9);
        $this->assertEqualsWithDelta(0.11, $ligne->cout_fcfa, 0.001);
        $this->assertSame(1, LigneDeConsommation::where('fonction', 'titre')->count());
    }

    public function test_en_pause_aucun_modele_n_est_appele(): void
    {
        config(['assistant.budget.mensuel_fcfa' => 100]);
        LigneDeConsommation::create(['fonction' => 'question', 'modele' => 'x', 'fournisseur' => 'x', 'identifiant_modele' => 'x', 'cout_fcfa' => 500, 'taux_usd_fcfa' => 600]);
        Http::fake();
        $frames = [];
        $this->app->instance(UiMessageStream::class, new UiMessageStream(function (string $f) use (&$frames) { $frames[] = $f; }));

        $this->actingAs($this->user())->post(route('chatbot.message.stream'), ['message' => 'Bonjour'])->assertOk()->streamedContent();

        Http::assertNothingSent();
        $this->assertStringContainsString('Je me repose', implode('', $frames));
        $this->assertStringContainsString('Je me repose', ChatbotMessage::where('role', 'assistant')->latest('id')->value('content'));
    }

    public function test_le_resume_totalise_par_modele_et_par_personne(): void
    {
        $user = $this->user();
        foreach ([['or-gemini-flash-lite', 'economique', 10.5], ['or-gemini-flash-lite', 'economique', 4.5], ['or-gemini-flash', 'standard', 30]] as $i => [$modele, $palier, $fcfa]) {
            LigneDeConsommation::create(['user_id' => $user->id, 'message_id' => $i + 1, 'fonction' => 'question', 'modele' => $modele, 'fournisseur' => 'openrouter',
                'identifiant_modele' => $modele, 'palier' => $palier, 'tokens_entree' => 100, 'tokens_sortie' => 10, 'cout_usd' => $fcfa / 600, 'cout_fcfa' => $fcfa, 'taux_usd_fcfa' => 600]);
        }

        $r = app(\App\Domain\Assistant\Consommation\ResumeDeConsommation::class)->pour(now()->startOfDay(), now());

        $this->assertSame(45.0, $r['total']['cout_fcfa']);
        $this->assertSame(3, $r['total']['echanges']);
        $this->assertSame(['or-gemini-flash', 'or-gemini-flash-lite'], array_column($r['par_modele'], 'cle'));
        $this->assertSame(15.0, $r['par_modele'][1]['cout_fcfa']);
        $this->assertSame($user->name, $r['par_personne'][0]['nom']);
        $this->assertSame('normal', $r['budget']['etat']);
    }
}
