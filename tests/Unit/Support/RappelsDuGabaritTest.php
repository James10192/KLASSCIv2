<?php

namespace Tests\Unit\Support;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use App\Services\EvaluationGradingShortcutService;
use App\Services\EvaluationPublishShortcutService;
use App\Services\TimetableShortcutService;
use App\Support\RappelsDuGabarit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class RappelsDuGabaritTest extends TestCase
{
    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('esbtp_inscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('status');
            $table->string('workflow_step')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->annee = new ESBTPAnneeUniversitaire();
        $this->annee->id = 7;
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_les_inscriptions_en_attente_gardent_les_comptes_d_avant(): void
    {
        $lignes = [
            ['status' => 'en_attente', 'workflow_step' => 'prospect'],
            ['status' => 'pending', 'workflow_step' => null],
            ['status' => 'active', 'workflow_step' => 'documents_complets'],
            ['status' => 'active', 'workflow_step' => 'en_validation'],
            ['status' => 'active', 'workflow_step' => 'en_validation'],
            // Hors du compte : validée, autre année, supprimée.
            ['status' => 'active', 'workflow_step' => 'etudiant_cree'],
            ['status' => 'en_attente', 'workflow_step' => 'prospect', 'annee_universitaire_id' => 8],
            ['status' => 'en_attente', 'workflow_step' => 'prospect', 'deleted_at' => now()],
        ];
        foreach ($lignes as $ligne) {
            DB::table('esbtp_inscriptions')->insert($ligne + ['annee_universitaire_id' => 7]);
        }

        $resume = RappelsDuGabarit::inscriptionsEnAttente($this->annee);

        $this->assertSame(5, $resume['count']);
        $this->assertSame(['prospect' => 1, 'documents_complets' => 1, 'en_validation' => 2], $resume['by_step']);
    }

    public function test_les_inscriptions_sont_relues_du_cache_et_non_de_la_base(): void
    {
        DB::table('esbtp_inscriptions')->insert(['annee_universitaire_id' => 7, 'status' => 'en_attente', 'workflow_step' => 'prospect']);
        $this->assertSame(1, RappelsDuGabarit::inscriptionsEnAttente($this->annee)['count']);

        DB::table('esbtp_inscriptions')->insert(['annee_universitaire_id' => 7, 'status' => 'en_attente', 'workflow_step' => 'prospect']);
        DB::enableQueryLog();

        $this->assertSame(1, RappelsDuGabarit::inscriptionsEnAttente($this->annee)['count']);
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_le_resume_des_emplois_du_temps_est_calcule_une_fois_et_sans_les_modeles(): void
    {
        $service = Mockery::mock(TimetableShortcutService::class);
        $service->shouldReceive('getShortcutSummary')->once()->andReturn([
            'show' => true, 'total' => 1, 'missing' => 1, 'expired' => 0, 'expiring_soon' => 0,
            'items' => [['class' => new \stdClass(), 'status' => 'missing']],
        ]);
        $this->app->instance(TimetableShortcutService::class, $service);

        RappelsDuGabarit::emploisDuTemps($this->annee);
        $resume = RappelsDuGabarit::emploisDuTemps($this->annee);

        $this->assertSame(1, $resume['missing']);
        $this->assertArrayNotHasKey('items', $resume);
    }

    public function test_les_evaluations_a_publier_sont_calculees_une_fois(): void
    {
        $service = Mockery::mock(EvaluationPublishShortcutService::class);
        $service->shouldReceive('getShortcutSummary')->once()->andReturn(['show' => true, 'total' => 3]);
        $this->app->instance(EvaluationPublishShortcutService::class, $service);

        RappelsDuGabarit::evaluationsAPublier($this->annee);

        $this->assertSame(3, RappelsDuGabarit::evaluationsAPublier($this->annee)['total']);
    }

    public function test_les_notes_a_saisir_sont_gardees_par_utilisateur(): void
    {
        $alice = new User();
        $alice->id = 1;
        $bob = new User();
        $bob->id = 2;

        $service = Mockery::mock(EvaluationGradingShortcutService::class);
        $service->shouldReceive('getShortcutSummary')->with($this->annee, $alice)->once()->andReturn(['show' => true, 'total' => 4]);
        $service->shouldReceive('getShortcutSummary')->with($this->annee, $bob)->once()->andReturn(['show' => false]);
        $this->app->instance(EvaluationGradingShortcutService::class, $service);

        RappelsDuGabarit::notesASaisir($this->annee, $alice);

        $this->assertSame(4, RappelsDuGabarit::notesASaisir($this->annee, $alice)['total']);
        $this->assertFalse(RappelsDuGabarit::notesASaisir($this->annee, $bob)['show']);
    }

    public function test_la_cle_change_avec_le_jour(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 2));
        $hier = RappelsDuGabarit::cle('x', $this->annee);
        $this->travelTo(now()->setDate(2026, 10, 3));

        $this->assertNotSame($hier, RappelsDuGabarit::cle('x', $this->annee));
    }
}
