<?php

namespace Tests\Feature\API\CLI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Le journal d'audit vu du CLI : mesure sur place, purge des consultations heritees. */
class AuditJournalCliTest extends TestCase
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

    private function lignes(string $evenement, int $nombre): void
    {
        $maintenant = now();
        DB::table('audits')->insert(array_fill(0, $nombre, [
            'user_type' => User::class, 'user_id' => null, 'event' => $evenement,
            'auditable_type' => User::class, 'auditable_id' => 1,
            'old_values' => '{}', 'new_values' => '{}', 'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]));
    }

    public function test_l_etat_compte_et_mesure_la_requete_depuis_le_debut(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:read']);
        DB::table('audits')->delete();
        $this->lignes('updated', 3);
        $this->lignes('retrieved', 2);

        $this->getJson('/api/cli/audit/etat')->assertOk()
            ->assertJsonPath('data.lignes', 5)
            ->assertJsonPath('data.consultations', 2)
            ->assertJsonPath('data.depuis_le_debut.automatiques.resultat', 3)
            ->assertJsonStructure(['data' => ['depuis_le_debut' => ['liste' => ['ms', 'plan'], 'a_regarder' => ['ms', 'plan']]]]);
    }

    public function test_la_purge_simule_par_defaut_puis_ne_retire_que_les_consultations(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);
        DB::table('audits')->delete();
        $this->lignes('updated', 3);
        $this->lignes('retrieved', 4);

        $this->postJson('/api/cli/audit/purger-consultations')->assertOk()
            ->assertJsonPath('data.simulation', true)->assertJsonPath('data.a_supprimer', 4);
        $this->assertSame(7, DB::table('audits')->count());

        $this->postJson('/api/cli/audit/purger-consultations', ['dry' => false])->assertOk()
            ->assertJsonPath('data.supprimees', 4)->assertJsonPath('data.restantes', 0);
        $this->assertSame(0, DB::table('audits')->where('event', 'retrieved')->count());
        $this->assertSame(3, DB::table('audits')->count());
    }

    public function test_la_purge_exige_le_droit_d_administration(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:read']);

        $this->postJson('/api/cli/audit/purger-consultations', ['dry' => false])->assertForbidden();
    }
}
