<?php

namespace Tests\Feature\LMD;

use App\Enums\TypeSeance;
use Carbon\Carbon;
use Tests\TestCase;

class PlanningExamensSectionTest extends TestCase
{
    public function test_examens_section_renders_when_type_seance_is_already_cast_to_enum(): void
    {
        $html = view('esbtp.lmd.planning._examens_section', [
            'examensRows' => collect([
                $this->examRow(TypeSeance::EXAMEN, Carbon::parse('08:00'), Carbon::parse('10:00')),
            ]),
        ])->render();

        $this->assertStringContainsString('Examen', $html);
        $this->assertStringContainsString('ECUE Test', $html);
        $this->assertStringContainsString('08:00', $html);
        $this->assertStringContainsString('10:00', $html);
        $this->assertStringNotContainsString('2026-01-15 08:00:00', $html);
    }

    public function test_examens_section_renders_unknown_legacy_type_without_exception(): void
    {
        $html = view('esbtp.lmd.planning._examens_section', [
            'examensRows' => collect([
                $this->examRow('LEGACY_UNKNOWN', '08:00:00', '10:00:00'),
            ]),
        ])->render();

        $this->assertStringContainsString('LEGACY_UNKNOWN', $html);
        $this->assertStringContainsString('08:00', $html);
        $this->assertStringContainsString('10:00', $html);
    }

    private function examRow(mixed $typeSeance, mixed $heureDebut, mixed $heureFin): object
    {
        return (object) [
            'type_seance' => $typeSeance,
            'date_seance' => '2026-01-15',
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'teacher_id' => null,
            'salle' => 'A101',
            'teacher' => null,
            'emploiTemps' => (object) [
                'classe' => (object) [
                    'name' => 'Licence 1',
                ],
            ],
            'matiere' => (object) [
                'name' => 'ECUE Test',
                'code' => 'ECUE-TEST',
                'uniteEnseignement' => (object) [
                    'code' => 'UE-TEST',
                ],
            ],
        ];
    }
}
