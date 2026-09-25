<?php

namespace Tests\Feature\Audit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Le journal d'audit se charge au defilement, par tranches de 50. Une rafale
 * d'ecritures dans la meme seconde ne doit ni se repeter ni se perdre entre
 * deux tranches.
 */
class JournalAuditDefilementTest extends TestCase
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
        Cache::flush();

        foreach (['admin.access', 'security.audit.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $agent = User::factory()->create();
        $agent->givePermissionTo(['admin.access', 'security.audit.view']);
        $this->actingAs($agent);
    }

    public function test_une_rafale_de_la_meme_seconde_se_lit_en_tranches_sans_repetition(): void
    {
        $seconde = now()->startOfSecond();
        $lignes = [];
        for ($i = 1; $i <= 70; $i++) {
            $lignes[] = [
                'event' => 'updated', 'auditable_type' => User::class, 'auditable_id' => $i,
                'old_values' => '{}', 'new_values' => '{}', 'created_at' => $seconde, 'updated_at' => $seconde,
            ];
        }
        DB::table('audits')->insert($lignes);

        // Sur une petite table le hasard de l'ordre ne se voit pas : on verifie
        // aussi que la requete finit par un departage unique.
        $requetes = [];
        DB::listen(function ($q) use (&$requetes) {
            $requetes[] = $q->sql;
        });

        $ids = [];
        foreach ([1, 2] as $page) {
            $r = $this->getJson(route('esbtp.audit.data', ['page' => $page]))->assertOk();
            $ids = array_merge($ids, array_column($r->json('data'), 'id'));
        }

        // Le reste du journal (creation du compte de test) s'y ajoute.
        $total = DB::table('audits')->count();
        $this->assertLessThanOrEqual(100, $total, 'Deux tranches de 50 doivent tout couvrir.');
        $this->assertCount($total, $ids);
        $this->assertCount($total, array_unique($ids));

        $liste = collect($requetes)->first(fn ($sql) => str_contains($sql, 'from `audits`') && str_contains($sql, 'limit'));
        $this->assertMatchesRegularExpression('/order by `created_at` desc, `id` desc limit/', (string) $liste);
    }

    public function test_la_page_n_a_plus_de_boutons_precedent_suivant(): void
    {
        $html = $this->get(route('esbtp.audit.index'))->assertOk()
            ->assertSee('x-ref="basDeListe"', false)
            ->assertDontSee('Précédent')
            ->getContent();

        if ($chemin = env('AUDIT_HTML_DUMP')) {
            file_put_contents($chemin, $html);
        }
    }
}
