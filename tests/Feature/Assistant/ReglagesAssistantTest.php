<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Cles\CoffreDesCles;
use App\Domain\Assistant\Modeles\RegistreDesModeles;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Clés d'IA posées par l'école (écran des réglages, klassci-cli) : chiffrées en
 * base, jamais renvoyées, prioritaires sur le .env, et réservées aux comptes
 * qui gèrent le système.
 */
class ReglagesAssistantTest extends TestCase
{
    use DatabaseTransactions;

    private const CLE = 'sk-or-v1-test-0000000000000000000000000000abcd';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['admin.access', 'system.manage'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Cache::flush();
        config([
            'assistant.fournisseurs.openrouter.cle' => null,
            'assistant.fournisseurs.anthropic.cle' => null,
            'assistant.fournisseurs.openai.cle' => null,
            'assistant.fournisseurs.gemini.cle' => null,
            'assistant.fournisseurs.mistral.cle' => null,
            'assistant.fournisseurs.deepseek.cle' => null,
            'assistant.limites.pause_ms' => 0,
        ]);
    }

    private function administrateur(): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo(['admin.access', 'system.manage']);

        return $u;
    }

    public function test_la_cle_est_chiffree_en_base_et_jamais_renvoyee(): void
    {
        $this->actingAs($this->administrateur())
            ->putJson(route('esbtp.settings.assistant.cle'), ['fournisseur' => 'openrouter', 'cle' => self::CLE])
            ->assertOk()
            ->assertJsonPath('etat.fournisseurs.openrouter.source', 'reglages')
            ->assertJsonPath('etat.fournisseurs.openrouter.fin', 'abcd')
            ->assertDontSee(self::CLE);

        $brut = Setting::where('key', 'assistant_cle_openrouter')->value('value');
        $this->assertNotSame(self::CLE, $brut);
        $this->assertStringNotContainsString('sk-or-v1', $brut);
        $this->assertSame(self::CLE, app(CoffreDesCles::class)->lire('openrouter'));
    }

    public function test_la_cle_posee_rend_les_modeles_openrouter_disponibles_et_prime_sur_le_env(): void
    {
        config(['assistant.fournisseurs.openrouter.cle' => 'cle-du-serveur']);
        app(CoffreDesCles::class)->definir('openrouter', self::CLE);

        $modele = app(RegistreDesModeles::class)->tous()['or-gpt-4o-mini'];
        $this->assertTrue($modele->estConfigure());
        $this->assertSame(self::CLE, $modele->cleApi());
        $this->assertSame('or-gpt-4o-mini', app(RegistreDesModeles::class)->candidats()[0]->cle);
    }

    public function test_sans_gestion_du_systeme_la_cle_est_refusee(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('admin.access');

        $this->actingAs($u)
            ->putJson(route('esbtp.settings.assistant.cle'), ['fournisseur' => 'openrouter', 'cle' => self::CLE])
            ->assertForbidden();
        $this->assertNull(app(CoffreDesCles::class)->lire('openrouter'));
    }

    public function test_un_fournisseur_ou_un_modele_inconnu_est_refuse(): void
    {
        $this->actingAs($this->administrateur());

        $this->putJson(route('esbtp.settings.assistant.cle'), ['fournisseur' => 'inconnu', 'cle' => self::CLE])->assertStatus(422);
        $this->putJson(route('esbtp.settings.assistant.modele'), ['modele' => 'modele-fantome'])->assertStatus(422);
        $this->putJson(route('esbtp.settings.assistant.modele'), ['modele' => 'or-deepseek'])
            ->assertOk()->assertJsonPath('etat.modele_defaut', 'or-deepseek');
    }

    public function test_le_formulaire_generique_ne_peut_pas_ecrire_une_cle_en_clair(): void
    {
        app(CoffreDesCles::class)->definir('openrouter', self::CLE);

        $this->actingAs($this->administrateur())
            ->put(route('esbtp.settings.update'), ['setting_assistant_cle_openrouter' => 'en-clair']);

        $this->assertSame(self::CLE, app(CoffreDesCles::class)->lire('openrouter'));
    }

    public function test_le_cli_pose_la_cle_avec_cli_admin_seulement(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:read']);
        $this->putJson('/api/cli/assistant/cle', ['fournisseur' => 'openrouter', 'cle' => self::CLE])->assertForbidden();

        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);
        $this->putJson('/api/cli/assistant/cle', ['fournisseur' => 'openrouter', 'cle' => self::CLE])
            ->assertOk()
            ->assertJsonPath('data.fournisseurs.openrouter.fin', 'abcd')
            ->assertDontSee(self::CLE);

        $this->getJson('/api/cli/assistant')->assertOk()->assertDontSee(self::CLE);
        $this->deleteJson('/api/cli/assistant/cle/openrouter')->assertOk()
            ->assertJsonPath('data.fournisseurs.openrouter.source', 'aucune');
    }

    public function test_le_diagnostic_essaie_une_reponse_puis_un_appel_d_outil(): void
    {
        app(CoffreDesCles::class)->definir('openrouter', self::CLE);
        $texte = "data: {\"choices\":[{\"delta\":{\"content\":\"OK\"},\"finish_reason\":null}]}\n\n"
            . "data: {\"choices\":[{\"delta\":{},\"finish_reason\":\"stop\"}]}\n\ndata: [DONE]\n\n";
        $outil = "data: {\"choices\":[{\"delta\":{\"tool_calls\":[{\"index\":0,\"id\":\"c1\",\"type\":\"function\",\"function\":{\"name\":\"donner_heure\",\"arguments\":\"{\\\"fuseau\\\":\\\"UTC\\\"}\"}}]},\"finish_reason\":null}]}\n\n"
            . "data: {\"choices\":[{\"delta\":{},\"finish_reason\":\"tool_calls\"}]}\n\ndata: [DONE]\n\n";
        Http::fakeSequence()
            ->push($texte, 200, ['Content-Type' => 'text/event-stream'])
            ->push($outil, 200, ['Content-Type' => 'text/event-stream']);

        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);
        $r = $this->postJson('/api/cli/assistant/tester', ['modele' => 'or-gpt-4o-mini'])->assertOk();

        $r->assertJsonPath('data.resultats.0.texte.ok', true)
            ->assertJsonPath('data.resultats.0.outils.ok', true);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'openrouter.ai')
            && $req->hasHeader('Authorization', 'Bearer ' . self::CLE)
            && $req->hasHeader('X-Title', 'KLASSCI'));
    }
}
