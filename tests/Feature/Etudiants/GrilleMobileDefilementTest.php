<?php

namespace Tests\Feature\Etudiants;

use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Sur telephone, la grille de cartes s'arretait a sa premiere tranche : la
 * seule sentinelle du defilement vivait dans le tableau, cache sous 992px.
 * La grille a desormais la sienne, et la reponse d'une tranche porte ses cartes.
 */
class GrilleMobileDefilementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('students.view', 'web');
        Cache::flush();
        $user = User::factory()->create();
        $user->givePermissionTo(['admin.access', 'students.view']);
        $this->actingAs($user);
    }

    public function test_la_grille_mobile_a_sa_sentinelle_et_la_tranche_suivante_porte_des_cartes(): void
    {
        ESBTPEtudiant::factory()->count(45)->create();

        $html = $this->get(route('esbtp.etudiants.index'))
            ->assertOk()
            ->assertSee('id="etudiants-grid-mobile"', false)
            ->assertSee('id="etudiants-sentinel-mobile"', false)
            ->getContent();

        if ($chemin = env('ETUDIANTS_HTML_DUMP')) {
            file_put_contents($chemin, $html);
        }

        $tranche = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.etudiants.index', ['page' => 2]))
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('id="etudiants-grid-mobile"', $tranche);
        $this->assertGreaterThan(0, substr_count($tranche, 'class="student-card'));
    }
}
