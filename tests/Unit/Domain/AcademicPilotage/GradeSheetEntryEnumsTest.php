<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use PHPUnit\Framework\TestCase;

class GradeSheetEntryEnumsTest extends TestCase
{
    public function test_entry_status_exposes_exact_values_and_labels(): void
    {
        $labels = [
            'expected' => 'Attendue',
            'entered' => 'Saisie',
            'absent' => 'Absent',
            'exempt' => 'Dispensé',
            'not_applicable' => 'Non applicable',
        ];

        $this->assertSame(array_keys($labels), GradeSheetEntryStatus::values());

        foreach (GradeSheetEntryStatus::cases() as $status) {
            $this->assertSame($labels[$status->value], $status->label());
        }
    }

    public function test_entry_mode_is_limited_to_direct_and_paper(): void
    {
        $labels = [
            'direct' => 'Saisie directe',
            'paper' => 'Fiche papier',
        ];

        $this->assertSame(array_keys($labels), GradeSheetEntryMode::values());

        foreach (GradeSheetEntryMode::cases() as $mode) {
            $this->assertSame($labels[$mode->value], $mode->label());
        }
    }
}
