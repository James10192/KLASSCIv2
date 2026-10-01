<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use App\Services\Admissions\InscriptionWorkflowSettings as W;
use App\Services\RendezVous\RendezVousReglages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * `klassci settings:set` sur le parcours d'inscription. Une école dont personne
 * n'a encore ouvert /esbtp/settings n'a pas ces lignes en base : le CLI
 * répondait « introuvable » et le parcours ne pouvait pas être activé à distance.
 */
class WorkflowInscriptionCliSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
    }

    private function poser(string $cle, string $valeur)
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);

        return $this->postJson('/api/cli/settings', ['key' => $cle, 'value' => $valeur, 'apply' => true]);
    }

    private function ouvrirRendezVous(bool $ouvert): void
    {
        Setting::updateOrCreate(
            ['key' => RendezVousReglages::ENABLED],
            ['value' => $ouvert ? '1' : '0', 'type' => 'boolean', 'group' => 'inscriptions', 'is_required' => false],
        );
    }

    public function test_le_reglage_est_cree_puis_ecrit_meme_absent_de_la_base(): void
    {
        $this->assertNull(Setting::where('key', W::MODE)->first());

        $this->poser(W::MODE, W::MODE_CAISSE_AVANT_PIECES)->assertOk();

        $this->assertSame(W::MODE_CAISSE_AVANT_PIECES, Setting::where('key', W::MODE)->value('value'));
        // Les autres réglages du bloc existent, à leur valeur d'usine.
        $this->assertSame('0', Setting::where('key', W::ENABLED)->value('value'));
    }

    public function test_un_choix_inconnu_est_refuse(): void
    {
        $this->poser(W::ACCOUNT_ACTIVATION_STEP, 'demain')->assertStatus(422);
        $this->assertNotSame('demain', Setting::where('key', W::ACCOUNT_ACTIVATION_STEP)->value('value'));
    }

    public function test_une_bascule_attend_un_booleen(): void
    {
        $this->poser(W::NOTIFY_WHATSAPP, 'peut-etre')->assertStatus(422);
        $this->poser(W::NOTIFY_WHATSAPP, 'false')->assertOk();
        $this->assertSame('0', Setting::where('key', W::NOTIFY_WHATSAPP)->value('value'));
    }

    public function test_activer_le_parcours_sans_prise_de_rendez_vous_est_refuse(): void
    {
        $this->ouvrirRendezVous(false);
        $this->poser(W::MODE, W::MODE_CAISSE_AVANT_PIECES)->assertOk();

        // Rendez-vous exigé par défaut, prise de rendez-vous fermée : bloquant.
        $this->poser(W::ENABLED, '1')->assertStatus(422);
        $this->assertSame('0', Setting::where('key', W::ENABLED)->value('value'));

        $this->ouvrirRendezVous(true);
        $this->poser(W::ENABLED, '1')->assertOk();
        $this->assertTrue(app(W::class)->usesManagedWorkflow());
    }

    public function test_fermer_la_prise_de_rendez_vous_sous_un_parcours_qui_l_exige_est_refuse(): void
    {
        $this->ouvrirRendezVous(true);
        $this->poser(W::MODE, W::MODE_CAISSE_AVANT_PIECES)->assertOk();
        $this->poser(W::ENABLED, '1')->assertOk();

        $this->poser(RendezVousReglages::ENABLED, '0')->assertStatus(422);
        $this->assertSame('1', Setting::where('key', RendezVousReglages::ENABLED)->value('value'));
    }
}
