<?php

namespace Tests\Unit\Services;

use App\Services\Scoring\PersonnelAcademicObligationMetricsService;
use App\Services\Scoring\PersonnelScoreResult;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PersonnelAcademicObligationMetricsServiceTest extends TestCase
{
    private PersonnelAcademicObligationMetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'academic_obligations_test');
        config()->set('database.connections.academic_obligations_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('academic_obligations_test');

        Schema::create('esbtp_grade_sheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('assigned_processor_id')->nullable();
            $table->string('entry_mode');
            $table->string('status')->default('expected');
            $table->dateTime('expected_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('entered_at')->nullable();
            $table->softDeletes();
        });

        $this->service = new PersonnelAcademicObligationMetricsService;
    }

    public function test_teacher_obligation_uses_teacher_ownership_and_mode_specific_fulfilment(): void
    {
        $this->insertSheet([
            'teacher_id' => 7,
            'entry_mode' => 'paper',
            'submitted_at' => '2026-07-10 09:00:00',
        ]);
        $this->insertSheet([
            'teacher_id' => 7,
            'entry_mode' => 'direct',
            'entered_at' => '2026-07-10 10:00:00',
        ]);
        $this->insertSheet([
            'teacher_id' => 7,
            'entry_mode' => 'paper',
            'entered_at' => '2026-07-10 11:00:00',
            'submitted_at' => '2026-08-01 00:00:00',
        ]);
        $this->insertSheet([
            'teacher_id' => 7,
            'status' => 'cancelled',
            'submitted_at' => '2026-07-10 11:30:00',
        ]);
        $this->insertSheet([
            'teacher_id' => 99,
            'assigned_processor_id' => 7,
            'entry_mode' => 'paper',
            'submitted_at' => '2026-07-10 12:00:00',
        ]);

        $result = $this->service->teacherGradeObligation(7, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

        $this->assertSame(PersonnelScoreResult::STATE_MEASURABLE, $result['state']);
        $this->assertSame(2, $result['numerator']);
        $this->assertSame(3, $result['denominator']);
        $this->assertSame(67, $result['score']);
        $this->assertSame(1.0, $result['coverage']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['evidence_hash']);
    }

    public function test_delegated_entry_requires_explicit_processor_and_receipt(): void
    {
        $this->insertSheet([
            'assigned_processor_id' => 12,
            'received_at' => '2026-07-08 08:00:00',
            'entered_at' => '2026-07-08 09:00:00',
        ]);
        $this->insertSheet([
            'assigned_processor_id' => 12,
            'received_at' => '2026-07-09 08:00:00',
        ]);
        $this->insertSheet([
            'assigned_processor_id' => 12,
            'entered_at' => '2026-07-10 08:00:00',
        ]);
        $this->insertSheet([
            'assigned_processor_id' => 13,
            'received_at' => '2026-07-11 08:00:00',
            'entered_at' => '2026-07-11 09:00:00',
        ]);
        $this->insertSheet([
            'assigned_processor_id' => 12,
            'entry_mode' => 'direct',
            'received_at' => '2026-07-12 08:00:00',
            'entered_at' => '2026-07-12 09:00:00',
        ]);
        $this->insertSheet([
            'assigned_processor_id' => 12,
            'status' => 'cancelled',
            'received_at' => '2026-07-13 08:00:00',
            'entered_at' => '2026-07-13 09:00:00',
        ]);
        $this->insertSheet([
            'assigned_processor_id' => 12,
            'received_at' => '2026-07-14 08:00:00',
            'entered_at' => '2026-08-01 00:00:00',
        ]);

        $result = $this->service->delegatedEntryObligation(12, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

        $this->assertSame(PersonnelScoreResult::STATE_MEASURABLE, $result['state']);
        $this->assertSame(1, $result['numerator']);
        $this->assertSame(3, $result['denominator']);
        $this->assertSame(33, $result['score']);
    }

    public function test_zero_explicit_obligation_is_non_applicable(): void
    {
        $result = $this->service->delegatedEntryObligation(12, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

        $this->assertSame(PersonnelScoreResult::STATE_NON_APPLICABLE, $result['state']);
        $this->assertSame(0, $result['numerator']);
        $this->assertSame(0, $result['denominator']);
        $this->assertNull($result['coverage']);
        $this->assertNull($result['confidence']);
    }

    public function test_source_capabilities_are_cached_for_the_service_lifetime(): void
    {
        Schema::table('esbtp_grade_sheets', fn (Blueprint $table) => $table->dropColumn('status'));
        $service = new PersonnelAcademicObligationMetricsService;

        $first = $service->delegatedEntryObligation(12, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));
        Schema::table('esbtp_grade_sheets', fn (Blueprint $table) => $table->string('status')->default('expected'));
        $second = $service->delegatedEntryObligation(12, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));
        $fresh = (new PersonnelAcademicObligationMetricsService)
            ->delegatedEntryObligation(12, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

        $this->assertSame(PersonnelScoreResult::STATE_INSUFFICIENT_DATA, $first['state']);
        $this->assertSame(PersonnelScoreResult::STATE_INSUFFICIENT_DATA, $second['state']);
        $this->assertSame(PersonnelScoreResult::STATE_NON_APPLICABLE, $fresh['state']);
    }

    private function insertSheet(array $attributes): void
    {
        DB::table('esbtp_grade_sheets')->insert(array_merge([
            'teacher_id' => null,
            'assigned_processor_id' => null,
            'entry_mode' => 'paper',
            'status' => 'expected',
            'expected_at' => '2026-07-05 08:00:00',
            'submitted_at' => null,
            'received_at' => null,
            'entered_at' => null,
            'deleted_at' => null,
        ], $attributes));
    }
}
