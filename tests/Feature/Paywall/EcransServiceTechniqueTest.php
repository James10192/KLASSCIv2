<?php

namespace Tests\Feature\Paywall;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Le tableau de bord et les matricules du service technique se rendent, et le
 * tableau de bord lit l'abonnement dans adminKlassci (pas dans les reglages locaux).
 */
class EcransServiceTechniqueTest extends TestCase
{
    use DatabaseTransactions;

    private User $st;

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

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Permission::findOrCreate('module.technical_support.access', 'web');
        Role::findOrCreate('serviceTechnique', 'web')->givePermissionTo('module.technical_support.access');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->st = User::factory()->create();
        $this->st->assignRole('serviceTechnique');
    }

    public function test_le_tableau_de_bord_lit_la_fiche_adminklassci(): void
    {
        Http::fake(['master.test/*' => Http::response([
            'tenant_code' => 'ecole-test',
            'plan' => 'elite', 'plan_label' => 'Elite', 'status' => 'active',
            'admin_url' => 'https://master.test/admin/tenants/7',
            'subscription' => ['end_date' => now()->addDays(5)->toDateString(), 'is_expired' => false],
            'limits' => ['max_users' => 999999, 'max_inscriptions_per_year' => 999999],
            'current_usage' => ['users' => 10, 'inscriptions_per_year' => 20],
            'quota_status' => [],
            'blocked_features' => [],
        ], 200)]);

        $this->actingAs($this->st)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Service technique')
            ->assertSee('adminKlassci connecté')
            ->assertSee('Abonnement expire dans 5 jour(s)')
            ->assertSee('https://master.test/admin/tenants/7', false);
    }

    public function test_le_tableau_de_bord_dit_quand_le_master_ne_repond_pas(): void
    {
        Http::fake(['master.test/*' => Http::response('erreur', 500)]);

        $this->actingAs($this->st)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('adminKlassci injoignable');
    }

    public function test_la_page_des_matricules_se_rend(): void
    {
        Http::fake();

        $this->actingAs($this->st)->get(route('esbtp.matricule-config.index'))
            ->assertOk()
            ->assertSee('Matricules étudiants')
            ->assertSee('id="matriculeMode"', false)
            ->assertSee('id="currentNomenclature"', false);
    }
}
