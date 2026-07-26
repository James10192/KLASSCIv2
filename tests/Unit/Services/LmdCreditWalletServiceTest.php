<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\ESBTPEtudiant;
use App\Services\LMD\LmdCreditWalletService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Unit\Domain\OfficialDocuments\OfficialDocumentDatabaseTestCase;

final class LmdCreditWalletServiceTest extends OfficialDocumentDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('esbtp_lmd_bulletins', function (Blueprint $table): void {
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->unsignedInteger('credits_capitalises')->nullable();
            $table->unsignedInteger('credits_totaux')->nullable();
        });
        Schema::create('esbtp_unites_enseignement', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_matieres', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_lmd_resultats_ues', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bulletin_id');
            $table->unsignedBigInteger('unite_enseignement_id')->nullable();
            $table->unsignedBigInteger('etudiant_id');
            $table->decimal('moyenne', 5, 2)->nullable();
            $table->string('statut')->nullable();
            $table->unsignedInteger('credit')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_lmd_resultats_ecues', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bulletin_id');
            $table->unsignedBigInteger('resultat_ue_id')->nullable();
            $table->unsignedBigInteger('matiere_id')->nullable();
            $table->unsignedBigInteger('etudiant_id');
            $table->decimal('moyenne', 5, 2)->nullable();
            $table->unsignedInteger('credit')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_lmd_deliberations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bulletin_id');
            $table->string('decision')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_wallet_counts_only_published_lmd_bulletins(): void
    {
        $this->seedIssuableJury();

        DB::table('esbtp_lmd_bulletins')->where('id', 100)->update([
            'moyenne_generale' => 13,
            'credits_capitalises' => 30,
            'credits_totaux' => 30,
            'is_published' => true,
            'decision_deliberation' => 'admis',
        ]);

        DB::table('esbtp_lmd_bulletins')->insert([
            'id' => 102,
            'etudiant_id' => 10,
            'classe_id' => 1,
            'parcours_id' => 1,
            'annee_universitaire_id' => 1,
            'semestre' => 2,
            'moyenne_generale' => 8,
            'credits_capitalises' => 12,
            'credits_totaux' => 30,
            'is_published' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $wallet = (new LmdCreditWalletService)->forStudent(ESBTPEtudiant::query()->findOrFail(10), 1, [1, 2]);

        self::assertSame(30, $wallet['capitalises']);
        self::assertSame(30, $wallet['totaux']);
        self::assertSame(100.0, $wallet['progression_pct']);
        self::assertTrue($wallet['has_unpublished_items']);
        self::assertCount(1, $wallet['entries']);
        self::assertSame(100, $wallet['entries']->first()['bulletin_id']);
    }
}
