<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Services\AcademicPilotageSummaryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicPilotageSummaryServiceTest extends AcademicPilotageDatabaseTestCase
{
    public function test_terminal_alerts_are_excluded_from_active_kpis(): void
    {
        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('systeme_academique')->nullable();
            $table->softDeletes();
        });
        DB::table('esbtp_classes')->insert([
            ['id' => 10, 'systeme_academique' => 'BTS'],
            ['id' => 11, 'systeme_academique' => 'LMD'],
        ]);
        $this->insertAlert('open-blocking', 'missing_grade', 'blocking', 'open');
        $this->insertAlert('progress-bulletin', 'bulletin_blocked', 'warning', 'in_progress');
        $this->insertAlert('resolved-blocking', 'missing_grade', 'blocking', 'resolved');
        $this->insertAlert('dismissed-bulletin', 'bulletin_blocked', 'warning', 'dismissed');
        $this->insertAlert('lmd-open', 'missing_grade', 'blocking', 'open', 11);
        $this->createGradeSheet([
            'status' => GradeSheetStatus::EXPECTED->value,
            'semester' => 'semestre1',
            'academic_system' => 'BTS',
        ]);
        $this->createGradeSheet([
            'status' => GradeSheetStatus::VALIDATED->value,
            'semester' => 'semestre1',
            'academic_system' => 'BTS',
        ]);

        $summary = (new AcademicPilotageSummaryService)->summarize(
            20,
            'semestre1',
            'BTS',
            null,
        );

        $this->assertSame(2, $summary['open_alerts']);
        $this->assertSame(1, $summary['blocking_alerts']);
        $this->assertSame(1, $summary['bulletin_blockers']);
        $this->assertSame(1, $summary['sheets_pending']);
    }

    private function insertAlert(
        string $fingerprint,
        string $type,
        string $severity,
        string $status,
        int $classId = 10,
    ): void
    {
        DB::table('esbtp_academic_alerts')->insert([
            'fingerprint' => hash('sha256', $fingerprint),
            'type' => $type,
            'severity' => $severity,
            'status' => $status,
            'annee_universitaire_id' => 20,
            'semester' => 'semestre1',
            'classe_id' => $classId,
            'message' => 'Alerte de test',
            'source_version' => '1',
            'detected_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
