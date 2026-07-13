<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPAnneeUniversitaire;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ESBTPAnneeUniversitaireDisplayNameTest extends TestCase
{
    public function test_display_name_uses_name_first(): void
    {
        $year = new ESBTPAnneeUniversitaire(['name' => '2025-2026']);

        $this->assertSame('2025-2026', $year->display_name);
    }

    public function test_display_name_falls_back_to_dates(): void
    {
        $year = new ESBTPAnneeUniversitaire([
            'start_date' => Carbon::parse('2025-09-01'),
            'end_date' => Carbon::parse('2026-07-31'),
        ]);

        $this->assertSame('2025-2026', $year->display_name);
    }

    public function test_display_name_falls_back_to_legacy_year_columns(): void
    {
        $year = new ESBTPAnneeUniversitaire;
        $year->setRawAttributes([
            'annee_debut' => 2024,
            'annee_fin' => 2025,
        ], true);

        $this->assertSame('2024-2025', $year->display_name);
    }

    public function test_display_name_normalizes_full_legacy_dates(): void
    {
        $year = new ESBTPAnneeUniversitaire;
        $year->setRawAttributes([
            'annee_debut' => '2023-09-01 00:00:00',
            'annee_fin' => '2024-07-31 00:00:00',
        ], true);

        $this->assertSame('2023-2024', $year->display_name);
    }

    public function test_display_name_handles_partial_dates(): void
    {
        $year = new ESBTPAnneeUniversitaire([
            'start_date' => Carbon::parse('2022-09-01'),
        ]);

        $this->assertSame('2022', $year->display_name);
    }

    public function test_display_name_never_returns_dash_for_empty_legacy_dates(): void
    {
        $year = new ESBTPAnneeUniversitaire;
        $year->id = 7;

        $this->assertSame('Année #7', $year->display_name);
    }
}
