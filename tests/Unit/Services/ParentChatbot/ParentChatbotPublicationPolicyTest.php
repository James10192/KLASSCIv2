<?php

namespace Tests\Unit\Services\ParentChatbot;

use App\Services\ParentChatbot\ParentChatbotPublicationPolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParentChatbotPublicationPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('esbtp_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_published')->default(false);
            $table->boolean('notes_published')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('evaluation_id');
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_bulletins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->boolean('is_published')->default(false);
            $table->boolean('signature_directeur')->default(false);
            $table->boolean('signature_responsable')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_grade_submission_runs_inside_a_transaction_after_locking_the_note_and_evaluation(): void
    {
        $evaluationId = DB::table('esbtp_evaluations')->insertGetId([
            'is_published' => true, 'notes_published' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $noteId = DB::table('esbtp_notes')->insertGetId([
            'etudiant_id' => 11, 'evaluation_id' => $evaluationId, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $transactionLevel = 0;
        $result = (new ParentChatbotPublicationPolicy)->submitIfStillPublishable([
            'type' => 'grades', 'student_id' => 11, 'resource_ids' => [$noteId],
        ], function () use (&$transactionLevel): string {
            $transactionLevel = DB::transactionLevel();

            return 'submitted';
        });

        $this->assertTrue($result['publication_eligible']);
        $this->assertSame('submitted', $result['result']);
        $this->assertGreaterThan(0, $transactionLevel);
    }

    public function test_grade_submission_is_suppressed_when_the_locked_evaluation_is_depublished(): void
    {
        $evaluationId = DB::table('esbtp_evaluations')->insertGetId([
            'is_published' => true, 'notes_published' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $noteId = DB::table('esbtp_notes')->insertGetId([
            'etudiant_id' => 11, 'evaluation_id' => $evaluationId, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $called = false;
        $result = (new ParentChatbotPublicationPolicy)->submitIfStillPublishable([
            'type' => 'grades', 'student_id' => 11, 'resource_ids' => [$noteId],
        ], function () use (&$called): void {
            $called = true;
        });

        $this->assertFalse($result['publication_eligible']);
        $this->assertNull($result['result']);
        $this->assertFalse($called);
    }

    public function test_report_card_submission_is_suppressed_when_a_signature_is_removed(): void
    {
        $bulletinId = DB::table('esbtp_bulletins')->insertGetId([
            'etudiant_id' => 11,
            'is_published' => true,
            'signature_directeur' => false,
            'signature_responsable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = (new ParentChatbotPublicationPolicy)->submitIfStillPublishable([
            'type' => 'report_card', 'student_id' => 11, 'resource_ids' => [$bulletinId],
        ], fn (): string => 'submitted');

        $this->assertFalse($result['publication_eligible']);
        $this->assertNull($result['result']);
    }
}
