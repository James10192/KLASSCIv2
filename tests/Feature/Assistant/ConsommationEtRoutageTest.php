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

    public function test_un_echec_fait_monter_la_conversation_une_limite_non(): void
    {
        $routeur = app(Routeur::class);
        $decision = $routeur->decider('Combien d\'inscrits ?', null);

        $echec = new ResultatBoucle('erreur', '', '', [], 'or-gemini-flash-lite', 'openrouter', [], 0, 0, 1, 10);
        $bute = new ResultatBoucle('ok', '', '', [], 'or-gemini-flash-lite', 'openrouter', [], 0, 0, 2, 10, [], echecsOutils: 1);
        $limite = new ResultatBoucle('limite', 'Partiel', '', [], 'or-gemini-flash-lite', 'openrouter', [], 0, 0, 8, 10);
        $reussi = new ResultatBoucle('ok', 'Il y a 214 inscrits.', 'Il y a 214 inscrits.', [], 'or-gemini-flash-lite', 'openrouter', [], 0, 0, 2, 10);

        $this->assertSame(['palier' => 'standard', 'succes_au_palier' => 0], $routeur->palierApres($decision, $echec));
        $this->assertSame(['palier' => 'standard', 'succes_au_palier' => 0], $routeur->palierApres($decision, $bute));
        // Un modèle plus cher atteindrait le même plafond de tours, en coûtant plus.
        $this->assertNull($routeur->palierApres($decision, $limite));
        $this->assertNull($routeur->palierApres($decision, $reussi));
    }

    public function test_trois_reussites_redescendent_le_palier_monte(): void
    {
        $routeur = app(Routeur::class);
        $reussi = new ResultatBoucle('ok', 'Réponse', 'Réponse', [], 'or-gemini-flash', 'openrouter', [], 0, 0, 1, 10);
        $conversation = new ChatbotConversation(['context' => ['palier' => 'avance', 'succes_au_palier' => 0]]);

        foreach ([1, 2] as $n) {
            $apres = $routeur->palierApres($routeur->decider('Combien ?', $conversation), $reussi, $conversation);
            $this->assertSame(['palier' => 'avance', 'succes_au_palier' => $n], $apres);
            $conversation->context = $apres;
        }
        $apres = $routeur->palierApres($routeur->decider('Combien ?', $conversation), $reussi, $conversation);
        $this->assertSame(['palier' => 'standard', 'succes_au_palier' => 0], $apres);

        // Du standard, la descente suivante ramène au premier palier : plus rien à retenir.
        $conversation->context = ['palier' => 'standard', 'succes_au_palier' => 2];
        $this->assertSame(['palier' => null, 'succes_au_palier' => 0], $routeur->palierApres($routeur->decider('Combien ?', $conversation), $reussi, $conversation));
    }

    public function test_sans_openrouter_une_ecole_anthropic_part_sur_haiku_pas_sur_sonnet(): void
    {
        config([
            'assistant.fournisseurs.openrouter.cle' => null,
            'assistant.paliers' => [
                'economique' => ['or-gemini-flash-lite', 'gpt-4o-mini'],
                'standard' => ['or-gemini-flash', 'claude-haiku'],
                'avance' => ['claude-sonnet'],
            ],
        ]);

        $this->assertSame(['claude-haiku', 'claude-sonnet'], $this->cles(app(Routeur::class)->decider('Combien d\'inscrits ?', null)->candidats));
    }

    public function test_le_modele_par_defaut_de_l_ecole_prend_la_tete_de_son_palier_et_l_etat_le_dit(): void
    {
        app(\App\Domain\Assistant\Reglages\ReglagesAssistant::class)->choisirModele('or-gpt-4o-mini');

        $this->assertSame(['or-gpt-4o-mini', 'or-gemini-flash-lite'], app(Routeur::class)->paliers()['economique']);
        $this->assertSame('or-gpt-4o-mini', app(\App\Domain\Assistant\Reglages\ReglagesAssistant::class)->etat()['modele_effectif']);
    }

    public function test_budget_atteint_palier_economique_seulement_puis_pause(): void
    {
        config(['assistant.budget.mensuel_fcfa' => 1000]);
        LigneDeConsommation::create(['fonction' => 'question', 'modele' => 'x', 'fournisseur' => 'x', 'identifiant_modele' => 'x', 'cout_fcfa' => 1050, 'taux_usd_fcfa' => 600]);
        app(BudgetAssistant::class)->oublier();

        $decision = app(Routeur::class)->decider('Compare les deux classes', null);
        $this->assertSame('budget_atteint', $decision->raison);
        $this->assertSame(['or-gemini-flash-lite', 'or-gpt-4o-mini'], $this->cles($decision->candidats));
        // Un modèle choisi à la main n'échappe pas au palier économique.
        $this->assertSame(['or-gemini-flash-lite', 'or-gpt-4o-mini'], $this->cles(app(Routeur::class)->decider('Bonjour', null, false, 'claude-sonnet')->candidats));
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

    public function test_le_budget_fixe_dans_adminklassci_prime(): void
    {
        config(['app.tenant_code' => 'presentation', 'assistant.budget.mensuel_fcfa' => 50000]);
        \Illuminate\Support\Facades\Cache::put('paywall_limits_presentation', ['assistant' => ['budget_mensuel_fcfa' => 12000]], 300);
        $this->assertSame(12000.0, app(BudgetAssistant::class)->budgetMensuelFcfa());

        // Le master dit « sans limite » (0) : c'est lui qui décide.
        \Illuminate\Support\Facades\Cache::put('paywall_limits_presentation', ['assistant' => ['budget_mensuel_fcfa' => 0]], 300);
        $this->assertNull(app(BudgetAssistant::class)->budgetMensuelFcfa());

        // Le master n'a rien fixé : le budget de l'école s'applique.
        \Illuminate\Support\Facades\Cache::put('paywall_limits_presentation', ['plan' => 'elite'], 300);
        $this->assertSame(50000.0, app(BudgetAssistant::class)->budgetMensuelFcfa());
    }

    public function test_un_modele_abandonne_pour_le_suivant_est_compte_en_echec(): void
    {
        // Le premier candidat (OpenRouter) tombe avant d'avoir rien montré ; Claude Sonnet reprend.
        Http::fake([
            'openrouter.test/*' => Http::response(['error' => ['message' => 'x']], 500),
            'api.anthropic.test/*' => Http::sequence()
                ->push(FluxEnregistres::anthropicTexte(), 200, ['Content-Type' => 'text/event-stream'])
                ->push(FluxEnregistres::anthropicTexte('Titre'), 200, ['Content-Type' => 'text/event-stream']),
        ]);
        config(['assistant.paliers' => ['economique' => ['or-gemini-flash-lite'], 'avance' => ['claude-sonnet']], 'assistant.limites.tentatives' => 1]);
        $this->app->instance(UiMessageStream::class, new UiMessageStream(fn () => null));

        $this->actingAs($this->user())->post(route('chatbot.message.stream'), ['message' => 'Bonjour'])->assertOk()->streamedContent();

        $lignes = LigneDeConsommation::where('fonction', 'question')->orderBy('id')->get();
        $this->assertSame(['or-gemini-flash-lite', 'claude-sonnet'], $lignes->pluck('modele')->all());
        $this->assertSame(['echec_fournisseur', 'ok'], $lignes->pluck('statut')->all());
    }

    public function test_reessayer_sans_diffusion_monte_aussi_le_palier(): void
    {
        $spy = \Mockery::spy(\App\Domain\Assistant\Assistant::class);
        $spy->shouldReceive('repondre')->andReturn(['text' => 'ok', 'tool_calls' => [], 'display_type' => 'text', 'display_data' => null, 'deep_link' => null, 'erreur' => false, 'interrompu' => false, 'modele' => null, 'parties' => [], 'trace' => [], 'suites' => [], 'consommation' => [], 'palier' => null]);
        $spy->shouldReceive('genererTitre')->andReturn('Titre');
        $this->app->instance(\App\Domain\Assistant\Assistant::class, $spy);

        $this->actingAs($this->user());
        app(\App\Services\Chatbot\ChatbotService::class)->sendMessage('Bonjour', null, null, null, true);

        $spy->shouldHaveReceived('repondre')->withArgs(fn (...$a) => ($a[7] ?? null) === true);
    }

    public function test_budget_atteint_sans_palier_economique_joignable_le_seul_modele_le_moins_cher(): void
    {
        config([
            'assistant.fournisseurs.openrouter.cle' => null,
            'assistant.budget.mensuel_fcfa' => 100,
            'assistant.paliers' => ['economique' => ['or-gemini-flash-lite'], 'standard' => ['claude-haiku'], 'avance' => ['claude-sonnet']],
        ]);
        LigneDeConsommation::create(['fonction' => 'question', 'modele' => 'x', 'fournisseur' => 'x', 'identifiant_modele' => 'x', 'cout_fcfa' => 105, 'taux_usd_fcfa' => 600]);
        app(BudgetAssistant::class)->oublier();

        // Haiku (1 $ + 5 $) plutôt que Sonnet (3 $ + 15 $), et lui seul.
        $this->assertSame(['claude-haiku'], $this->cles(app(Routeur::class)->decider('Bonjour', null)->candidats));
    }

    public function test_un_titre_rate_est_compte_en_echec_et_jamais_a_zero(): void
    {
        config(['assistant.fournisseurs.openrouter.cle' => null, 'assistant.paliers' => ['avance' => ['claude-sonnet']]]);
        Http::fake(['api.anthropic.test/*' => Http::response(['error' => ['type' => 'overloaded_error']], 529)]);

        $this->assertNull(app(\App\Domain\Assistant\Assistant::class)->genererTitre('Combien d\'inscrits ?', null, null));

        $ligne = LigneDeConsommation::where('fonction', 'titre')->sole();
        $this->assertSame('echec_fournisseur', $ligne->statut);
        // Aucun usage rapporté : l'entrée est estimée, pas comptée nulle.
        $this->assertGreaterThan(0, $ligne->tokens_entree);
    }

    public function test_la_conversation_oublie_son_palier_une_fois_redescendue(): void
    {
        $spy = \Mockery::spy(\App\Domain\Assistant\Assistant::class);
        $spy->shouldReceive('repondre')->andReturn(['text' => 'ok', 'tool_calls' => [], 'display_type' => 'text', 'display_data' => null, 'deep_link' => null, 'erreur' => false, 'interrompu' => false, 'modele' => null, 'parties' => [], 'trace' => [], 'suites' => [], 'consommation' => [], 'palier' => ['palier' => null, 'succes_au_palier' => 0]]);
        $spy->shouldReceive('genererTitre')->andReturn('Titre');
        $this->app->instance(\App\Domain\Assistant\Assistant::class, $spy);
        $user = $this->user();
        $this->actingAs($user);
        $conversation = ChatbotConversation::create(['user_id' => $user->id, 'session_id' => (string) Str::uuid(), 'title' => 'x', 'context' => ['palier' => 'standard', 'succes_au_palier' => 2], 'last_activity_at' => now()]);

        app(\App\Services\Chatbot\ChatbotService::class)->sendMessage('Bonjour', $conversation->session_id);

        $contexte = $conversation->fresh()->context ?? [];
        $this->assertArrayNotHasKey('palier', $contexte);
        $this->assertArrayNotHasKey('succes_au_palier', $contexte);
    }

    public function test_un_budget_fixe_par_adminklassci_ne_se_modifie_pas_depuis_l_ecole(): void
    {
        config(['app.tenant_code' => 'presentation']);
        \Illuminate\Support\Facades\Cache::put('paywall_limits_presentation', ['assistant' => ['budget_mensuel_fcfa' => 12000]], 300);
        $reglages = app(\App\Domain\Assistant\Reglages\ReglagesAssistant::class);

        $this->assertSame('master', $reglages->etat()['budget']['source']);
        $this->expectExceptionMessage('adminKlassci');
        $reglages->definirBudget(5000);
    }

    public function test_l_etat_ne_prete_pas_de_preference_a_l_ecole_ni_de_modele_en_pause(): void
    {
        $reglages = app(\App\Domain\Assistant\Reglages\ReglagesAssistant::class);
        $this->assertNull($reglages->etat()['modele_defaut']);

        config(['assistant.budget.mensuel_fcfa' => 10]);
        LigneDeConsommation::create(['fonction' => 'question', 'modele' => 'x', 'fournisseur' => 'x', 'identifiant_modele' => 'x', 'cout_fcfa' => 50, 'taux_usd_fcfa' => 600]);
        app(BudgetAssistant::class)->oublier();
        $this->assertNull($reglages->etat()['modele_effectif']);
    }
}
