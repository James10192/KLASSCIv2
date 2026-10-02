<?php

namespace Tests\Unit\Support;

use App\Models\ESBTPAnneeUniversitaire;
use App\Support\RappelsDuGabarit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RappelsDuGabaritTest extends TestCase
{
    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();

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
}
