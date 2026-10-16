<?php

namespace Tests\Unit\Services\Notes;

use App\Models\ESBTPInscription;
use App\Services\Notes\EvaluationsDeLaFiche;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EvaluationsDeLaFicheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:',
            'prefix' => '', 'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');
        Schema::create('esbtp_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    public function test_only_exact_class_and_year_are_visible_on_an_inscription(): void
    {
        DB::table('esbtp_evaluations')->insert([
            ['id' => 1, 'classe_id' => 10, 'annee_universitaire_id' => 2026, 'deleted_at' => null],
            ['id' => 2, 'classe_id' => 11, 'annee_universitaire_id' => 2026, 'deleted_at' => null],
            ['id' => 3, 'classe_id' => 10, 'annee_universitaire_id' => 2025, 'deleted_at' => null],
            ['id' => 4, 'classe_id' => 10, 'annee_universitaire_id' => 2026, 'deleted_at' => now()],
        ]);
        $inscription = new ESBTPInscription();
        $inscription->classe_id = 10;
        $inscription->annee_universitaire_id = 2026;

        $this->assertSame([1], app(EvaluationsDeLaFiche::class)
            ->pourInscription($inscription)->pluck('id')->all());
    }

    public function test_no_matching_evaluation_stays_an_empty_not_cross_enrolment_list(): void
    {
        $inscription = new ESBTPInscription();
        $inscription->classe_id = 42;
        $inscription->annee_universitaire_id = 2026;

        $this->assertSame([], app(EvaluationsDeLaFiche::class)
            ->pourInscription($inscription)->pluck('id')->all());
    }
}
