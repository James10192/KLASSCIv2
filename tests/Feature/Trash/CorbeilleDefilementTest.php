<?php

namespace Tests\Feature\Trash;

use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La corbeille ne montrait que ses 20 derniers elements, sans suite. Ses
 * reponses portent desormais la pagination, et une suppression en masse
 * (meme seconde) se lit par tranches sans repetition.
 */
class CorbeilleDefilementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_les_inscriptions_supprimees_se_lisent_par_tranches(): void
    {
        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['trash.view', 'identity.enrollment_officer'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Cache::flush();
        $agent = User::factory()->create();
        $agent->givePermissionTo(['trash.view', 'identity.enrollment_officer']);
        $this->actingAs($agent);

        $ids = ESBTPInscription::factory()->count(25)->create()->pluck('id');
        DB::table('esbtp_inscriptions')->whereIn('id', $ids)->update(['deleted_at' => now()->startOfSecond()]);
        $total = ESBTPInscription::onlyTrashed()->count();

        $vus = [];
        $page = 1;
        do {
            $r = $this->getJson(route('esbtp.trash.inscriptions', ['page' => $page]))->assertOk();
            $vus = array_merge($vus, array_column($r->json('items'), 'id'));
            $this->assertSame($total, $r->json('pagination.total'));
            $page++;
        } while ($r->json('pagination.has_more') && $page < 10);

        $this->assertCount($total, $vus);
        $this->assertCount($total, array_unique($vus));
        $this->assertCount(20, $this->getJson(route('esbtp.trash.inscriptions'))->json('items'));
    }

    public function test_la_taille_d_une_tranche_est_bornee(): void
    {
        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['trash.view', 'identity.enrollment_officer'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $agent = User::factory()->create();
        $agent->givePermissionTo(['trash.view', 'identity.enrollment_officer']);

        $this->actingAs($agent)->getJson(route('esbtp.trash.inscriptions', ['per_page' => 100000]))
            ->assertOk()
            ->assertJsonPath('pagination.affiches', fn ($n) => $n <= 100);
    }

    public function test_la_page_n_appelle_plus_init_deux_fois(): void
    {
        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['trash.view', 'identity.enrollment_officer'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $agent = User::factory()->create();
        $agent->givePermissionTo(['trash.view', 'identity.enrollment_officer']);

        $html = $this->actingAs($agent)->get(route('esbtp.trash.index'))
            ->assertOk()
            ->assertSee('x-ref="basDeListe"', false)
            ->assertDontSee('x-data="trashIndex()" x-init="init()"', false)
            ->getContent();

        if ($chemin = env('TRASH_HTML_DUMP')) {
            file_put_contents($chemin, $html);
        }
    }
}
