<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\AcademicAlertSeverity;
use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Domain\AcademicPilotage\Enums\AcademicResponsibility;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\AcademicActorAssignment;
use App\Domain\AcademicPilotage\Models\AcademicAlert;
use App\Domain\AcademicPilotage\Models\AcademicAlertEvent;
use App\Domain\AcademicPilotage\Models\AcademicMetricSnapshot;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetDocument;
use App\Domain\AcademicPilotage\Models\GradeSheetEntry;
use App\Domain\AcademicPilotage\Models\GradeSheetEvent;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class AcademicPilotageModelsTest extends TestCase
{
    public function test_models_use_the_expected_tables(): void
    {
        $tables = [
            GradeSheet::class => 'esbtp_grade_sheets',
            GradeSheetEntry::class => 'esbtp_grade_sheet_entries',
            GradeSheetEvent::class => 'esbtp_grade_sheet_events',
            GradeSheetDocument::class => 'esbtp_grade_sheet_documents',
            AcademicActorAssignment::class => 'esbtp_academic_actor_assignments',
            AcademicAlert::class => 'esbtp_academic_alerts',
            AcademicAlertEvent::class => 'esbtp_academic_alert_events',
            AcademicMetricSnapshot::class => 'esbtp_academic_metric_snapshots',
        ];

        foreach ($tables as $model => $table) {
            $this->assertSame($table, (new $model)->getTable());
        }
    }

    public function test_models_cast_workflow_fields_to_canonical_enums(): void
    {
        $this->assertSame(GradeSheetStatus::class, (new GradeSheet)->getCasts()['status']);
        $this->assertSame(GradeSheetEntryMode::class, (new GradeSheet)->getCasts()['entry_mode']);
        $this->assertSame(GradeSheetEntryStatus::class, (new GradeSheetEntry)->getCasts()['status']);
        $this->assertSame(
            AcademicResponsibility::class,
            (new AcademicActorAssignment)->getCasts()['responsibility']
        );
        $this->assertSame(AcademicAlertStatus::class, (new AcademicAlert)->getCasts()['status']);
        $this->assertSame(AcademicAlertSeverity::class, (new AcademicAlert)->getCasts()['severity']);
    }

    public function test_workflow_authority_fields_are_not_mass_assignable(): void
    {
        $gradeSheet = new GradeSheet;
        $entry = new GradeSheetEntry;
        $alert = new AcademicAlert;

        $this->assertContains('obligation_key', $gradeSheet->getFillable());
        $this->assertNotContains('lock_version', $gradeSheet->getFillable());

        foreach (['status', 'assigned_processor_id', 'validated_by', 'validated_at'] as $field) {
            $this->assertNotContains($field, $gradeSheet->getFillable());
        }

        foreach (['note_id', 'status', 'entered_by', 'validated_by'] as $field) {
            $this->assertNotContains($field, $entry->getFillable());
        }

        foreach (['status', 'assignee_id', 'acknowledged_by', 'resolved_by', 'dismissed_by'] as $field) {
            $this->assertNotContains($field, $alert->getFillable());
        }

        $this->assertNotContains('uploaded_by', (new GradeSheetDocument)->getFillable());
        foreach (['actor_id', 'from_status', 'to_status'] as $field) {
            $this->assertNotContains($field, (new GradeSheetEvent)->getFillable());
            $this->assertNotContains($field, (new AcademicAlertEvent)->getFillable());
        }
        $this->assertNotContains('created_by', (new AcademicActorAssignment)->getFillable());
        $this->assertNotContains('updated_by', (new AcademicActorAssignment)->getFillable());
    }

    public function test_append_only_models_register_update_and_delete_guards(): void
    {
        foreach ([GradeSheetEvent::class, AcademicAlertEvent::class, GradeSheetDocument::class] as $model) {
            new $model;
            $dispatcher = $model::getEventDispatcher();

            $this->assertNotEmpty($dispatcher->getListeners("eloquent.updating: $model"));
            $this->assertNotEmpty($dispatcher->getListeners("eloquent.deleting: $model"));
        }
    }

    public function test_domain_models_remain_focused(): void
    {
        $models = [
            GradeSheet::class,
            GradeSheetEntry::class,
            GradeSheetEvent::class,
            GradeSheetDocument::class,
            AcademicActorAssignment::class,
            AcademicAlert::class,
            AcademicAlertEvent::class,
            AcademicMetricSnapshot::class,
        ];

        foreach ($models as $model) {
            $modelFile = (new ReflectionClass($model))->getFileName();
            $publicMethods = array_filter(
                (new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC),
                fn (ReflectionMethod $method) => $method->getFileName() === $modelFile
            );

            $this->assertLessThanOrEqual(12, count($publicMethods), "$model est trop chargé.");
        }
    }
}
