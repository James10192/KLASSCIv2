<?php

namespace Tests\Feature\Audit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Le journal d'audit se lit comme des phrases, par onglet, et se charge au
 * defilement par tranches de 50.
 */
class JournalAuditDefilementTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Cache::flush();

        foreach (['admin.access', 'security.audit.view', 'comptabilite.audit.view', 'comptabilite.sensitive.access'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->agent = User::factory()->create(['name' => 'Aminata BAMBA']);
        $this->agent->givePermissionTo(['admin.access', 'security.audit.view']);
        $this->actingAs($this->agent);
    }

    /** @param array<string, mixed> $valeurs */
    private function audit(array $valeurs): int
    {
        $midi = now()->setTime(12, 0);

        return DB::table('audits')->insertGetId($valeurs + [
            'user_type' => User::class, 'user_id' => $this->agent->id,
            'event' => 'updated', 'auditable_type' => User::class, 'auditable_id' => $this->agent->id,
            'old_values' => '{}', 'new_values' => '{}', 'created_at' => $midi, 'updated_at' => $midi,
        ]);
    }

    private function tranche(array $parametres = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.audit.index', $parametres + ['mode' => 'rows']))->assertOk();
    }

    public function test_une_rafale_de_la_meme_seconde_se_lit_en_tranches_sans_repetition(): void
    {
        $seconde = now()->startOfSecond();
        $lignes = [];
        for ($i = 1; $i <= 70; $i++) {
            $lignes[] = [
                'user_type' => User::class, 'user_id' => $this->agent->id,
                'event' => 'updated', 'auditable_type' => User::class, 'auditable_id' => $i,
                'old_values' => '{}', 'new_values' => '{}', 'created_at' => $seconde, 'updated_at' => $seconde,
            ];
        }
        DB::table('audits')->insert($lignes);

        $requetes = [];
        DB::listen(function ($q) use (&$requetes) {
            $requetes[] = $q->sql;
        });

        $ids = [];
        foreach ([1, 2] as $page) {
            preg_match_all('/class="jda-ligne[^"]*" data-li-cle="(\d+)"/', $this->tranche(['page' => $page])->json('rows_html'), $m);
            $ids = array_merge($ids, $m[1]);
        }

        $total = DB::table('audits')->whereNotNull('user_id')->count();
        $this->assertCount($total, $ids);
        $this->assertCount($total, array_unique($ids));

        $liste = collect($requetes)->first(fn ($sql) => str_contains($sql, 'from `audits`') && str_contains($sql, 'limit'));
        $this->assertMatchesRegularExpression('/order by `created_at` desc, `id` desc limit/', (string) $liste);
    }

    public function test_chaque_action_se_lit_comme_une_phrase_sans_identifiant(): void
    {
        $this->audit(['old_values' => json_encode(['username' => 'abamba']), 'new_values' => json_encode(['username' => 'aminata.b'])]);

        $this->get(route('esbtp.audit.index'))->assertOk()
            ->assertSee('Aminata BAMBA', false)
            ->assertSee('a modifié le compte de', false)
            ->assertSee('abamba → aminata.b', false)
            ->assertDontSee('Précédent');
    }

    public function test_les_taches_automatiques_sont_masquees_et_comptees(): void
    {
        DB::table('audits')->delete();
        $this->audit([]);
        $this->audit(['user_id' => null, 'user_type' => null]);
        $this->audit(['user_id' => null, 'user_type' => null]);

        $this->get(route('esbtp.audit.index'))->assertOk()
            ->assertSee('2 actions automatiques', false)
            ->assertSee('Les afficher');

        preg_match_all('/class="jda-ligne/', $this->tranche(['auto' => 1])->json('rows_html'), $m);
        $this->assertCount(3, $m[0]);
    }

    public function test_un_filtre_ne_recharge_que_la_liste_et_le_compte_a_regarder(): void
    {
        $this->audit(['event' => 'deleted', 'auditable_type' => 'Spatie\Permission\Models\Role', 'auditable_id' => 999,
            'old_values' => json_encode(['name' => 'caissier'])]);

        $r = $this->getJson(route('esbtp.audit.index', ['fragment' => 1, 'theme' => 'a_regarder']))->assertOk();
        $this->assertSame(1, $r->json('aRegarder'));
        $this->assertStringContainsString('Droits modifiés', $r->json('liste'));
    }

    public function test_le_detail_dit_ce_qui_a_change_et_la_vie_de_l_objet(): void
    {
        $this->audit(['event' => 'created', 'created_at' => now()->subHours(3)]);
        $id = $this->audit(['old_values' => json_encode(['username' => 'abamba']), 'new_values' => json_encode(['username' => 'aminata.b'])]);

        $this->get(route('esbtp.audit.show', $id))->assertOk()
            ->assertSee('CE QUI A ÉTÉ TOUCHÉ')
            ->assertSee('CE QUI A CHANGÉ')
            ->assertSee('aminata.b')
            ->assertSee('TOUTE SON HISTOIRE')
            ->assertSee('cette action')
            ->assertSee('DÉTAILS TECHNIQUES');
    }

    public function test_le_droit_comptable_seul_n_ouvre_que_les_finances(): void
    {
        $comptable = User::factory()->create();
        $comptable->givePermissionTo(['admin.access', 'comptabilite.audit.view']);
        $compte = $this->audit([]);
        $frais = $this->audit(['auditable_type' => 'App\Models\ESBTPFraisCategory', 'auditable_id' => 4242,
            'event' => 'created', 'new_values' => json_encode(['name' => 'Frais d\'examen'])]);
        $paiement = $this->audit(['auditable_type' => 'App\Models\ESBTPPaiement', 'auditable_id' => 4243]);

        $this->actingAs($comptable);
        $this->get(route('esbtp.audit.index'))->assertOk()
            ->assertDontSee('Comptes et droits')
            ->assertSee('Frais d&#039;examen', false);
        $this->get(route('esbtp.audit.show', $frais))->assertOk();
        $this->get(route('esbtp.audit.show', $compte))->assertForbidden();
        // L'argent lui-meme demande en plus l'acces aux donnees sensibles.
        $this->get(route('esbtp.audit.show', $paiement))->assertForbidden();
    }

    public function test_l_ancien_audit_comptable_mene_a_l_onglet_finances(): void
    {
        $this->agent->givePermissionTo('comptabilite.audit.view');

        $this->get(route('esbtp.audit.comptabilite'))
            ->assertStatus(301)
            ->assertRedirect(route('esbtp.audit.index', ['theme' => 'finances']));
    }
}
