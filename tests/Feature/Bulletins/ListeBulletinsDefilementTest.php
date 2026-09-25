<?php

namespace Tests\Feature\Bulletins;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * La liste des bulletins se charge au defilement. Une generation en masse
 * ecrit toute une classe dans la meme seconde : les tranches ne doivent ni
 * repeter ni perdre un bulletin.
 */
class ListeBulletinsDefilementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_une_classe_generee_dans_la_meme_seconde_se_lit_par_tranches(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Cache::flush();
        foreach (['admin.access', 'bulletins.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $agent = User::factory()->create();
        $agent->givePermissionTo(['admin.access', 'bulletins.view']);
        $this->actingAs($agent);

        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $classe = ESBTPClasse::factory()->create();
        ESBTPBulletin::factory()->count(24)->create([
            'annee_universitaire_id' => $annee->id,
            'classe_id' => $classe->id,
            'periode' => 'semestre1',
            'created_at' => now()->startOfSecond(),
        ]);
        $filtre = ['annee_universitaire_id' => $annee->id, 'classe_id' => $classe->id];

        $page = $this->get(route('esbtp.bulletins.index', $filtre))
            ->assertOk()
            ->assertSee('data-page-suivante="2"', false)
            ->assertDontSee('bul-pager', false)
            ->getContent();

        $suite = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.bulletins.index', $filtre + ['page' => 2, 'mode' => 'rows']))
            ->assertOk()
            ->assertJsonPath('pagination.total', 24)
            ->assertJsonMissingPath('stats')
            ->json('rows_html');

        preg_match_all('/<tr data-li-cle="(\d+)"/', $page, $a);
        preg_match_all('/<tr data-li-cle="(\d+)"/', $suite, $b);
        $this->assertCount(20, $a[1]);
        $this->assertCount(4, $b[1]);
        $this->assertCount(24, array_unique(array_merge($a[1], $b[1])));
    }

    public function test_apres_une_action_seules_les_lignes_touchees_reviennent_avec_les_compteurs(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Cache::flush();
        foreach (['admin.access', 'bulletins.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $agent = User::factory()->create();
        $agent->givePermissionTo(['admin.access', 'bulletins.view']);
        $this->actingAs($agent);

        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $classe = ESBTPClasse::factory()->create();
        [$a, $b, $c] = ESBTPBulletin::factory()->count(3)->create([
            'annee_universitaire_id' => $annee->id, 'classe_id' => $classe->id, 'is_published' => false,
        ])->all();
        $a->update(['is_published' => true]);

        $filtre = ['annee_universitaire_id' => $annee->id, 'page' => 1, 'mode' => 'rows', 'lignes' => [$a->id, $b->id]];
        $ajax = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);

        $r = $ajax->getJson(route('esbtp.bulletins.index', $filtre))->assertOk()
            ->assertJsonPath('stats.published', 1)
            ->assertJsonPath('stats.total', 3);
        preg_match_all('/<tr data-li-cle="(\d+)"/', $r->json('rows_html'), $m);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], array_map('intval', $m[1]), 'Seulement les deux lignes touchées, pas la troisième.');

        // Sous le filtre « non publiés », la ligne publiée n'est plus rendue :
        // l'écran la retire sur place.
        $r = $ajax->getJson(route('esbtp.bulletins.index', $filtre + ['published' => '0']))->assertOk();
        preg_match_all('/<tr data-li-cle="(\d+)"/', $r->json('rows_html'), $m);
        $this->assertSame([$b->id], array_map('intval', $m[1]));
    }
}
