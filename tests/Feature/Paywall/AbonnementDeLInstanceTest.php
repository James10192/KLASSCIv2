<?php

namespace Tests\Feature\Paywall;

use App\Models\ESBTPSystemSetting;
use App\Models\User;
use App\Services\Master\AbonnementDeLInstance;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * L'abonnement de l'instance se lit dans adminKlassci ; les reglages locaux ne
 * sont plus qu'un secours, et l'ecran comme le middleware le disent.
 */
class AbonnementDeLInstanceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'services.master.api_url' => 'https://master.test/api',
            'services.master.api_token' => 'jeton',
            'app.tenant_code' => 'ecole-test',
        ]);
        Cache::flush();
    }

    private function reponseMaster(array $surcharge = []): array
    {
        return array_replace_recursive([
            'tenant_code' => 'ecole-test',
            'tenant_name' => 'École Test',
            'plan' => 'elite',
            'plan_label' => 'Elite',
            'monthly_fee' => 400000,
            'status' => 'active',
            'admin_url' => 'https://master.test/admin/tenants/7',
            'subscription' => ['start_date' => now()->subMonths(2)->toDateString(), 'end_date' => now()->addDays(40)->toDateString(), 'is_expired' => false, 'days_remaining' => 40],
            'limits' => ['max_users' => 999999, 'max_staff' => 50, 'max_students' => 3000, 'max_inscriptions_per_year' => 999999, 'max_storage_mb' => 5120],
            'current_usage' => ['users' => 73, 'staff' => 20, 'students' => 1200, 'inscriptions_per_year' => 317, 'storage_mb' => 1024],
            'quota_status' => ['is_over_quota' => false, 'users_over_limit' => false, 'staff_over_limit' => false, 'students_over_limit' => false, 'inscriptions_over_limit' => false, 'storage_over_limit' => false],
            'blocked_features' => [],
            'last_stats_update' => now()->subHour()->toIso8601String(),
        ], $surcharge);
    }

    public function test_la_fiche_adminklassci_fait_foi_quand_le_master_repond(): void
    {
        Http::fake(['master.test/*' => Http::response($this->reponseMaster(), 200)]);
        ESBTPSystemSetting::setValue('paywall_plan_name', 'Offre Partenaire');
        ESBTPSystemSetting::setValue('paywall_max_users', 5);

        $etat = app(AbonnementDeLInstance::class)->etat();

        $this->assertSame('master', $etat['source']);
        $this->assertTrue($etat['master_joignable']);
        $this->assertSame('Elite', $etat['plan_label']);
        $this->assertSame(400000, $etat['tarif_mensuel']);
        $this->assertSame('https://master.test/admin/tenants/7', $etat['fiche_url']);
        $this->assertTrue($etat['usages']['users']['illimite']);
        $this->assertSame(40, $etat['abonnement']['jours_restants']);
        $this->assertNotNull($etat['lu_a']);
        // Le reglage local de 5 utilisateurs ne bloque plus rien.
        $this->assertFalse($etat['statut']['is_blocked']);
    }

    public function test_master_injoignable_les_valeurs_locales_servent_et_le_disent(): void
    {
        Http::fake(['master.test/*' => Http::response('erreur', 503)]);
        ESBTPSystemSetting::setValue('paywall_plan_name', 'Offre Partenaire');
        ESBTPSystemSetting::setValue('paywall_max_users', 50);

        $etat = app(AbonnementDeLInstance::class)->etat();

        $this->assertSame('local', $etat['source']);
        $this->assertTrue($etat['master_configure']);
        $this->assertFalse($etat['master_joignable']);
        $this->assertSame('adminKlassci a répondu 503', $etat['erreur_master']);
        $this->assertSame('Offre Partenaire', $etat['plan_label']);
        $this->assertSame(50, $etat['usages']['users']['max']);
        // Ce que l'instance ne mesure pas reste « non mesure », jamais zero.
        $this->assertNull($etat['usages']['staff']['actuel']);
        $this->assertNull($etat['usages']['storage']['actuel']);
        // Lien deduit de MASTER_API_URL, sans domaine ecrit en dur.
        $this->assertSame('https://master.test/admin/tenants?tableSearch=ecole-test', $etat['fiche_url']);
    }

    public function test_sans_master_configure_aucun_appel_et_mode_secours(): void
    {
        config(['services.master.api_url' => null]);
        Http::fake();

        $etat = app(AbonnementDeLInstance::class)->etat();

        Http::assertNothingSent();
        $this->assertFalse($etat['master_configure']);
        $this->assertNull($etat['master_joignable']);
        $this->assertNull($etat['fiche_url']);
    }

    public function test_le_blocage_suit_la_fiche_expiration_et_depassement(): void
    {
        Http::fake(['master.test/*' => Http::response($this->reponseMaster([
            'subscription' => ['end_date' => now()->subDays(3)->toDateString(), 'is_expired' => true],
            'limits' => ['max_students' => 1000],
            'current_usage' => ['students' => 1200],
            'quota_status' => ['is_over_quota' => true, 'students_over_limit' => true],
        ]), 200)]);

        $statut = app(AbonnementDeLInstance::class)->statutDeBlocage();

        $this->assertTrue($statut['is_blocked']);
        $this->assertStringStartsWith('Abonnement expiré le ', $statut['reasons'][0]);
        $this->assertSame('Limite d\'étudiants dépassée (1 200/1 000)', $statut['reasons'][1]);
    }

    public function test_une_limite_tout_juste_atteinte_ne_bloque_pas(): void
    {
        // Le master pose students_over_limit des l'egalite (>=) ; l'ecole a
        // 1000/1000 n'est pas en faute pour autant.
        Http::fake(['master.test/*' => Http::response($this->reponseMaster([
            'limits' => ['max_students' => 1000],
            'current_usage' => ['students' => 1000],
            'quota_status' => ['is_over_quota' => false, 'students_over_limit' => true],
        ]), 200)]);

        $etat = app(AbonnementDeLInstance::class)->etat();

        $this->assertFalse($etat['statut']['is_blocked']);
        $this->assertFalse($etat['usages']['students']['depasse']);
        $this->assertSame('Proche de la limite d\'étudiants (1 000/1 000)', $etat['statut']['warnings'][0]);
    }

    public function test_master_injoignable_les_valeurs_locales_previennent_sans_bloquer(): void
    {
        Http::fake(['master.test/*' => Http::response('erreur', 503)]);
        ESBTPSystemSetting::setValue('subscription_end_date', now()->subDays(10)->toDateString());

        $statut = app(AbonnementDeLInstance::class)->statutDeBlocage();

        $this->assertFalse($statut['is_blocked']);
        $this->assertSame([], $statut['reasons']);
        $this->assertStringContainsString('adminKlassci injoignable', $statut['warnings'][0]);
    }

    public function test_sans_master_les_valeurs_locales_bloquent_toujours(): void
    {
        config(['services.master.api_url' => null]);
        ESBTPSystemSetting::setValue('subscription_end_date', now()->subDays(10)->toDateString());

        $this->assertTrue(app(AbonnementDeLInstance::class)->statutDeBlocage()['is_blocked']);
    }

    public function test_actualiser_vide_le_cache_et_relit_le_master(): void
    {
        Http::fakeSequence('master.test/*')
            ->push($this->reponseMaster(['plan_label' => 'Essentiel']), 200)
            ->push($this->reponseMaster(['plan_label' => 'Elite']), 200);

        $this->assertSame('Essentiel', app(AbonnementDeLInstance::class)->etat()['plan_label']);
        // Lecture suivante servie par le cache : toujours Essentiel.
        $this->assertSame('Essentiel', app(AbonnementDeLInstance::class)->etat()['plan_label']);

        $reponse = $this->actingAs($this->serviceTechnique())
            ->postJson(route('esbtp.paywall-config.refresh'));

        $reponse->assertOk()->assertJsonPath('source', 'master')->assertJsonPath('master_joignable', true);
        $this->assertStringContainsString('Elite', $reponse->json('html'));
        Http::assertSentCount(2);
    }

    public function test_master_configure_le_plan_ne_s_ecrit_plus_en_local(): void
    {
        Http::fake(['master.test/*' => Http::response($this->reponseMaster(), 200)]);
        $st = $this->serviceTechnique();

        $this->actingAs($st)->postJson(route('esbtp.paywall-config.store'), [
            'is_active' => true, 'max_users' => 5, 'plan_name' => 'Local',
        ])->assertStatus(409);
        $this->assertNull(ESBTPSystemSetting::getValue('paywall_plan_name'));

        $this->actingAs($st)->postJson(route('esbtp.paywall-config.store'), ['is_active' => true])->assertOk();
        $this->assertTrue((bool) ESBTPSystemSetting::getValue('paywall_active'));

        $this->actingAs($st)->postJson(route('esbtp.paywall-config.extend'), ['months' => 3])->assertStatus(409);
    }

    public function test_la_page_de_blocage_s_ouvre_pour_une_ecole_sans_droit_paywall(): void
    {
        Http::fake(['master.test/*' => Http::response($this->reponseMaster(), 200)]);
        $this->withoutMiddleware([\App\Http\Middleware\CheckInstalled::class, \App\Http\Middleware\EnsureInstalled::class]);

        $this->actingAs(User::factory()->create())
            ->get(route('esbtp.paywall-config.upgrade'))
            ->assertOk()
            ->assertSee('Abonnement à régulariser')
            ->assertDontSee('https://master.test/admin/tenants/7');
    }

    public function test_l_ecran_du_service_technique_montre_la_fiche_et_masque_l_edition_locale(): void
    {
        Http::fake(['master.test/*' => Http::response($this->reponseMaster(), 200)]);

        $this->actingAs($this->serviceTechnique())
            ->get(route('esbtp.paywall-config.index'))
            ->assertOk()
            ->assertSee('Synchronisé avec adminKlassci')
            ->assertSee('https://master.test/admin/tenants/7', false)
            ->assertSee('Actualiser depuis adminKlassci')
            ->assertDontSee('Réglages locaux de secours');
    }

    public function test_sans_master_l_ecran_garde_l_edition_locale_libellee_secours(): void
    {
        config(['services.master.api_url' => null]);
        Http::fake();

        $this->actingAs($this->serviceTechnique())
            ->get(route('esbtp.paywall-config.index'))
            ->assertOk()
            ->assertSee('adminKlassci non configuré')
            ->assertSee('Réglages locaux de secours')
            ->assertSee('id="paywallConfigForm"', false);
    }

    private function serviceTechnique(): User
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Permission::findOrCreate('paywall.manage', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo('paywall.manage');

        return $user;
    }
}
