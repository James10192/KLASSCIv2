<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDCreditWalletEntry;
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

        // Moyenne et credits des bulletins : deja poses par le socle
        // (OfficialDocumentDatabaseTestCase), que le PV controle aussi.
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
        (require database_path('migrations/2026_07_26_015346_create_esbtp_lmd_credit_wallet_entries_table.php'))->up();
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
        self::assertSame('Ledger credits LMD', $wallet['source_label']);
        self::assertSame(2, ESBTPLMDCreditWalletEntry::query()->count());
    }

    public function test_wallet_ledger_is_idempotent_and_records_credit_deltas(): void
    {
        $this->seedIssuableJury();
        $student = ESBTPEtudiant::query()->findOrFail(10);
        $service = new LmdCreditWalletService;

        DB::table('esbtp_lmd_bulletins')->where('id', 100)->update([
            'moyenne_generale' => 13,
            'credits_capitalises' => 30,
            'credits_totaux' => 30,
            'is_published' => true,
            'decision_deliberation' => 'admis',
            'updated_at' => now(),
        ]);

        $first = $service->forStudent($student, 1, [1]);
        $second = $service->forStudent($student, 1, [1]);

        self::assertSame(30, $first['capitalises']);
        self::assertSame(30, $second['capitalises']);
        self::assertSame(1, ESBTPLMDCreditWalletEntry::query()->count());

        DB::table('esbtp_lmd_bulletins')->where('id', 100)->update([
            'credits_capitalises' => 24,
            'decision_deliberation' => 'admis_sous_condition',
            'updated_at' => now()->addMinute(),
        ]);

        $adjusted = $service->forStudent($student, 1, [1]);
        $delta = ESBTPLMDCreditWalletEntry::query()->where('source_id', 100)->orderByDesc('id')->firstOrFail();

        self::assertSame(24, $adjusted['capitalises']);
        self::assertSame(2, ESBTPLMDCreditWalletEntry::query()->count());
        self::assertSame(-6, $delta->credit_delta);
        self::assertSame('credit_adjusted', $delta->event_type);
    }
}