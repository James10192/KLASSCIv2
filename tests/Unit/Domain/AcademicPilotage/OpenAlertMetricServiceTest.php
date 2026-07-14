<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Domain\AcademicPilotage\Services\OpenAlertMetricService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OpenAlertMetricServiceTest extends AcademicPilotageDatabaseTestCase
{
    public function test_it_counts_only_active_alerts_in_the_student_academic_scope(): void
    {
        $this->insertAlert('open');
        $this->insertAlert('acknowledged');
        $this->insertAlert('in_progress');
        $this->insertAlert('resolved');
        $this->insertAlert('dismissed');
        $this->insertAlert('open', ['etudiant_id' => 102]);
        $this->insertAlert('open', ['annee_universitaire_id' => 21]);
        $this->insertAlert('open', ['classe_id' => 11]);
        $this->insertAlert('open', ['semester' => 'semestre2']);

        $metric = (new OpenAlertMetricService)->forStudent($this->context());

        $this->assertSame(25.0, $metric->value);
        $this->assertSame(100, $metric->confidencePct);
        $this->assertSame(['sample_count' => 3, 'engine_ready' => true], $metric->evidence);
    }

    public function test_it_is_unavailable_when_the_alert_table_is_missing(): void
    {
        Schema::drop('esbtp_academic_alerts');

        $metric = (new OpenAlertMetricService)->forStudent($this->context());

        $this->assertFalse($metric->isAvailable());
        $this->assertSame(0, $metric->confidencePct);
        $this->assertSame(['engine_ready' => false], $metric->evidence);
    }

    public function test_it_loads_alert_counts_once_for_every_student_in_the_same_scope(): void
    {
        $this->insertAlert('open');
        $this->insertAlert('open', ['etudiant_id' => 102]);
        $queries = 0;
        DB::listen(function ($query) use (&$queries): void {
            if (str_contains(strtolower($query->sql), 'from "esbtp_academic_alerts"')) {
                $queries++;
            }
        });
        $service = new OpenAlertMetricService;

        $first = $service->forStudent($this->context());
        $second = $service->forStudent(new StudentMetricContext(102, 10, 20, 'BTS', 'semestre1'));

        $this->assertSame(75.0, $first->value);
        $this->assertSame(75.0, $second->value);
        $this->assertSame(1, $queries);
    }

    private function context(): StudentMetricContext
    {
        return new StudentMetricContext(101, 10, 20, 'BTS', 'semestre1');
    }

    private function insertAlert(string $status, array $overrides = []): void
    {
        DB::table('esbtp_academic_alerts')->insert(array_merge([
            'fingerprint' => hash('sha256', uniqid('', true)),
            'type' => 'missing_grade',
            'severity' => 'warning',
            'status' => AcademicAlertStatus::from($status)->value,
            'annee_universitaire_id' => 20,
            'semester' => 'semestre1',
            'classe_id' => 10,
            'etudiant_id' => 101,
            'message' => 'Alerte de test.',
            'detected_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
