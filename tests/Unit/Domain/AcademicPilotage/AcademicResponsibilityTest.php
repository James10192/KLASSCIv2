<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\AcademicResponsibility;
use PHPUnit\Framework\TestCase;

class AcademicResponsibilityTest extends TestCase
{
    public function test_exposes_exact_values_and_french_labels(): void
    {
        $labels = [
            'grade_entry' => 'Saisie des notes',
            'sheet_reception' => 'Réception des fiches',
            'grade_control' => 'Contrôle des notes',
            'academic_followup' => 'Suivi académique',
        ];

        $this->assertSame(array_keys($labels), AcademicResponsibility::values());

        foreach (AcademicResponsibility::cases() as $responsibility) {
            $this->assertSame($labels[$responsibility->value], $responsibility->label());
        }
    }
}
