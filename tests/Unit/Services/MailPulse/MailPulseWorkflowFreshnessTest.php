<?php

namespace Tests\Unit\Services\MailPulse;

use App\Services\MailPulse\MailPulseWorkflowFreshness;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class MailPulseWorkflowFreshnessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('esbtp_inscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_paiements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('inscription_id');
            $table->string('status');
            $table->timestamp('date_validation')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('esbtp_attendances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('seance_cours_id');
            $table->date('date');
            $table->string('statut');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    public function test_pending_payment_reminder_is_cancelled_after_validation(): void
    {
        DB::table('esbtp_inscriptions')->insert(['id' => 1, 'etudiant_id' => 10]);
        DB::table('esbtp_paiements')->insert([
            'id' => 42, 'inscription_id' => 1, 'status' => 'en_attente',
        ]);
        $guard = app(MailPulseWorkflowFreshness::class);
        $payload = ['metadata' => ['paiement_id' => 42]];

        $this->assertTrue($guard->isCurrent('fee_reminder', 10, $payload));
        DB::table('esbtp_paiements')->where('id', 42)->update([
            'status' => 'validé', 'date_validation' => now(),
        ]);
        $this->assertFalse($guard->isCurrent('fee_reminder', 10, $payload));
        $this->assertTrue($guard->isCurrent('payment_received', 10, $payload));
        $this->assertFalse($guard->isCurrent('payment_received', 11, $payload));
        $this->assertFalse($guard->isCurrent('payment_rejected', 10, $payload));
        $this->assertFalse($guard->isCurrent('fee_reminder', 10, ['metadata' => []]));
    }

    public function test_corrected_attendance_blocks_old_absence_and_low_attendance_alerts(): void
    {
        $payload = ['metadata' => [
            'attendance_session_id' => 22, 'attendance_date' => '2026-10-13',
        ]];
        DB::table('esbtp_attendances')->insert([
            'etudiant_id' => 10, 'seance_cours_id' => 22,
            'date' => '2026-10-13', 'statut' => 'absent',
        ]);

        $guard = app(MailPulseWorkflowFreshness::class);
        $this->assertTrue($guard->isCurrent('absence_reported', 10, $payload));
        DB::table('esbtp_attendances')->insert([
            'etudiant_id' => 10, 'seance_cours_id' => 22,
            'date' => '2026-10-13', 'statut' => 'present',
        ]);
        $this->assertFalse($guard->isCurrent('absence_reported', 10, $payload));
        $this->assertFalse($guard->isCurrent('low_attendance', 10, $payload));
        $this->assertFalse($guard->isCurrent('absence_reported', 10, []));
    }

    public function test_unrelated_non_mutable_events_remain_eligible(): void
    {
        $this->assertTrue(app(MailPulseWorkflowFreshness::class)
            ->isCurrent('bulletin_published', 10, []));
    }
}
