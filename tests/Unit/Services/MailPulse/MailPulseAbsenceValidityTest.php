<?php

namespace Tests\Unit\Services\MailPulse;

use App\Services\MailPulse\MailPulseAbsenceValidity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MailPulseAbsenceValidityTest extends TestCase
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
        Schema::create('esbtp_attendances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seance_cours_id')->nullable();
            $table->unsignedBigInteger('etudiant_id');
            $table->date('date');
            $table->string('call_type')->nullable();
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

    public function test_a_corrected_roll_call_cancels_pending_absence_alerts(): void
    {
        $claim = ['metadata' => [
            'attendance_session_id' => 13,
            'attendance_date' => '2026-10-13',
        ]];
        DB::table('esbtp_attendances')->insert([
            'id' => 1, 'etudiant_id' => 41, 'seance_cours_id' => 13,
            'date' => '2026-10-13', 'call_type' => 'merged', 'statut' => 'absent',
        ]);
        $guard = app(MailPulseAbsenceValidity::class);
        $this->assertTrue($guard->stillAbsent('absence_reported', 41, $claim));
        $this->assertTrue($guard->stillAbsent('low_attendance', 41, $claim));

        DB::table('esbtp_attendances')->insert([
            'id' => 2, 'etudiant_id' => 41, 'seance_cours_id' => 13,
            'date' => '2026-10-13', 'call_type' => 'merged', 'statut' => 'present',
        ]);
        $this->assertFalse($guard->stillAbsent('absence_reported', 41, $claim));
        $this->assertFalse($guard->stillAbsent('low_attendance', 41, $claim));
        $this->assertFalse($guard->stillAbsent('absence_reported', 42, $claim));
    }

    public function test_absence_without_verifiable_session_fails_closed(): void
    {
        $guard = app(MailPulseAbsenceValidity::class);
        $this->assertFalse($guard->stillAbsent('absence_reported', 41, []));
        $this->assertTrue($guard->stillAbsent('payment_received', 41, []));
    }
}
