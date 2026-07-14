<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\AcademicAlertSeverity;
use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Domain\AcademicPilotage\Enums\AcademicAlertType;
use PHPUnit\Framework\TestCase;

class AcademicAlertEnumsTest extends TestCase
{
    public function test_alert_status_exposes_exact_values_and_labels(): void
    {
        $labels = [
            'open' => 'Ouverte',
            'acknowledged' => 'Prise en compte',
            'in_progress' => 'En cours de traitement',
            'resolved' => 'Résolue',
            'dismissed' => 'Classée sans suite',
        ];

        $this->assertSame(array_keys($labels), AcademicAlertStatus::values());

        foreach (AcademicAlertStatus::cases() as $status) {
            $this->assertSame($labels[$status->value], $status->label());
        }
    }

    public function test_alert_status_distinguishes_open_and_terminal_states(): void
    {
        $open = [
            AcademicAlertStatus::OPEN,
            AcademicAlertStatus::ACKNOWLEDGED,
            AcademicAlertStatus::IN_PROGRESS,
        ];

        foreach (AcademicAlertStatus::cases() as $status) {
            $expectedOpen = in_array($status, $open, true);
            $this->assertSame($expectedOpen, $status->isOpen());
            $this->assertSame(! $expectedOpen, $status->isTerminal());
        }
    }

    public function test_alert_status_exposes_the_manual_transition_graph(): void
    {
        $allowedTargets = [
            AcademicAlertStatus::OPEN->value => ['acknowledged', 'dismissed'],
            AcademicAlertStatus::ACKNOWLEDGED->value => ['in_progress', 'resolved', 'dismissed'],
            AcademicAlertStatus::IN_PROGRESS->value => ['resolved', 'dismissed'],
            AcademicAlertStatus::RESOLVED->value => [],
            AcademicAlertStatus::DISMISSED->value => [],
        ];

        foreach (AcademicAlertStatus::cases() as $status) {
            $this->assertSame(
                $allowedTargets[$status->value],
                array_map(
                    fn (AcademicAlertStatus $target): string => $target->value,
                    $status->allowedManualTransitions(),
                ),
            );
        }

        $this->assertSame([
            'acknowledged',
            'in_progress',
            'resolved',
            'dismissed',
        ], AcademicAlertStatus::manualTargetValues());
    }

    public function test_alert_severity_exposes_exact_values_and_labels(): void
    {
        $labels = [
            'info' => 'Information',
            'warning' => 'Avertissement',
            'critical' => 'Critique',
            'blocking' => 'Bloquante',
        ];

        $this->assertSame(array_keys($labels), AcademicAlertSeverity::values());

        foreach (AcademicAlertSeverity::cases() as $severity) {
            $this->assertSame($labels[$severity->value], $severity->label());
        }
    }

    public function test_alert_types_cover_academic_pilotage_detection_cases(): void
    {
        $this->assertSame([
            'assessment_not_configured',
            'assessment_not_created',
            'missing_grade',
            'grade_sheet_not_submitted',
            'grade_sheet_not_entered',
            'class_delayed',
            'student_no_average',
            'student_high_absence',
            'bulletin_blocked',
            'teacher_late',
            'educator_overloaded',
            'data_inconsistency',
        ], AcademicAlertType::values());
    }

    public function test_labels_do_not_contain_encoding_artifacts(): void
    {
        $labels = array_map(
            fn ($case) => $case->label(),
            [...AcademicAlertStatus::cases(), ...AcademicAlertSeverity::cases()]
        );

        $this->assertStringNotContainsString("\u{00C3}", implode(' ', $labels));
    }
}
