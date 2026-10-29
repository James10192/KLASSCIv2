<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use PHPUnit\Framework\TestCase;

class GradeSheetStatusTest extends TestCase
{
    public function test_exposes_exact_values_and_french_labels(): void
    {
        $labels = [
            'expected' => 'Attendue',
            'submitted' => 'Soumise',
            'received' => 'Reçue',
            'in_entry' => 'En cours de saisie',
            'entered' => 'Saisie',
            'controlled' => 'Contrôlée',
            'validated' => 'Validée',
            'rejected' => 'Rejetée',
            'correction_requested' => 'Correction demandée',
            'cancelled' => 'Annulée',
        ];

        $this->assertSame(array_keys($labels), GradeSheetStatus::values());

        foreach (GradeSheetStatus::cases() as $status) {
            $this->assertSame($labels[$status->value], $status->label());
        }
    }

    public function test_only_completed_outcomes_are_terminal(): void
    {
        $terminal = [
            GradeSheetStatus::VALIDATED,
            GradeSheetStatus::REJECTED,
            GradeSheetStatus::CANCELLED,
        ];

        foreach (GradeSheetStatus::cases() as $status) {
            $this->assertSame(in_array($status, $terminal, true), $status->isTerminal());
        }
    }
}
