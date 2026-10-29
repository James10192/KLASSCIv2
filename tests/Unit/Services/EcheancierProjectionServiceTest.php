<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPEcheancierRuleLine;
use App\Services\EcheancierProjectionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class EcheancierProjectionServiceTest extends TestCase
{
    public function test_it_projects_percent_lines_and_adjusts_rounding_delta(): void
    {
        $service = new EcheancierProjectionService();

        $lines = collect([
            $this->line('Acompte', 1, 'percent', 33.33, 'days_after_inscription', '0'),
            $this->line('Solde', 2, 'percent', 33.33, 'days_after_inscription', '30'),
        ]);

        $projected = $service->projectDueLines(
            1000,
            $lines,
            Carbon::parse('2026-05-04'),
            itemKey: 'mandatory:1',
            categoryId: 10,
            categoryName: 'Scolarite'
        );

        $this->assertCount(2, $projected);
        $this->assertSame(333.3, $projected[0]['amount']);
        $this->assertSame(666.7, $projected[1]['amount']);
        $this->assertSame(1000.0, array_sum(array_column($projected, 'amount')));
        $this->assertSame('2026-05-04', $projected[0]['due_date']);
        $this->assertSame('2026-06-03', $projected[1]['due_date']);
        $this->assertSame(10, $projected[0]['category_id']);
    }

    public function test_it_uses_fallback_when_fixed_date_is_not_a_calendar_date(): void
    {
        $service = new EcheancierProjectionService();

        $lines = collect([
            $this->line('Invalid', 1, 'percent', 100, 'fixed_mm_dd', '02-31'),
        ]);

        $projected = $service->projectDueLines(
            500,
            $lines,
            Carbon::parse('2026-01-10'),
            fallbackDays: 45,
            itemKey: 'optional:2'
        );

        $this->assertSame('2026-02-24', $projected[0]['due_date']);
    }

    public function test_it_returns_single_fallback_line_without_active_rules(): void
    {
        $service = new EcheancierProjectionService();

        $projected = $service->projectDueLines(
            750,
            new Collection(),
            Carbon::parse('2026-05-04'),
            fallbackDays: 10,
            itemKey: 'fallback'
        );

        $this->assertCount(1, $projected);
        $this->assertSame(750.0, $projected[0]['amount']);
        $this->assertSame('2026-05-14', $projected[0]['due_date']);
    }

    private function line(
        string $label,
        int $sortOrder,
        string $amountMode,
        float $amountValue,
        string $dueMode,
        string $dueValue
    ): ESBTPEcheancierRuleLine {
        return new ESBTPEcheancierRuleLine([
            'label' => $label,
            'sort_order' => $sortOrder,
            'amount_mode' => $amountMode,
            'amount_value' => $amountValue,
            'due_mode' => $dueMode,
            'due_value' => $dueValue,
            'grace_days' => 0,
            'is_active' => true,
        ]);
    }
}
