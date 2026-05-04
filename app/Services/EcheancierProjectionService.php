<?php

namespace App\Services;

use App\Models\ESBTPEcheancierRuleLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EcheancierProjectionService
{
    /**
     * @param float $itemAmount
     * @param Collection<int, ESBTPEcheancierRuleLine> $ruleLines
     * @return array<int, array<string, mixed>>
     */
    public function projectDueLines(
        float $itemAmount,
        Collection $ruleLines,
        Carbon $referenceDate,
        int $fallbackDays = 30,
        string $itemKey = '',
        ?int $categoryId = null,
        ?string $categoryName = null
    ): array {
        $amount = round(max(0, $itemAmount), 2);

        if ($amount <= 0) {
            return [];
        }

        $activeLines = $ruleLines
            ->filter(fn ($line) => (bool) ($line->is_active ?? true))
            ->sortBy('sort_order')
            ->values();

        if ($activeLines->isEmpty()) {
            return [$this->fallbackLine($amount, $referenceDate, $fallbackDays, $itemKey, $categoryId, $categoryName)];
        }

        $projected = [];
        $runningTotal = 0.0;

        foreach ($activeLines as $index => $line) {
            $lineAmount = $this->resolveLineAmount($line, $amount);
            $dueDate = $this->resolveDueDate($line, $referenceDate, $fallbackDays);

            $lineAmount = round(max(0, $lineAmount), 2);
            $runningTotal += $lineAmount;

            $projected[] = [
                'line_key' => $itemKey . ':line:' . ($index + 1),
                'item_key' => $itemKey,
                'label' => $line->label ?: ('Tranche ' . ($index + 1)),
                'category_id' => $categoryId,
                'category_name' => $categoryName,
                'amount' => $lineAmount,
                'due_date' => $dueDate->toDateString(),
                'grace_days' => max(0, (int) ($line->grace_days ?? 0)),
            ];
        }

        $delta = round($amount - $runningTotal, 2);
        if (!empty($projected) && abs($delta) >= 0.01) {
            $lastIndex = count($projected) - 1;
            $projected[$lastIndex]['amount'] = round(max(0, $projected[$lastIndex]['amount'] + $delta), 2);
        }

        $projected = array_values(array_filter($projected, fn ($line) => $line['amount'] > 0));

        if (empty($projected)) {
            return [$this->fallbackLine($amount, $referenceDate, $fallbackDays, $itemKey, $categoryId, $categoryName)];
        }

        return $projected;
    }

    private function resolveLineAmount(ESBTPEcheancierRuleLine $line, float $itemAmount): float
    {
        if ($line->amount_mode === ESBTPEcheancierRuleLine::AMOUNT_MODE_PERCENT) {
            return $itemAmount * ((float) $line->amount_value / 100);
        }

        return (float) $line->amount_value;
    }

    private function resolveDueDate(ESBTPEcheancierRuleLine $line, Carbon $referenceDate, int $fallbackDays): Carbon
    {
        if ($line->due_mode === ESBTPEcheancierRuleLine::DUE_MODE_FIXED_MM_DD) {
            if (preg_match('/^(\d{2})-(\d{2})$/', (string) $line->due_value, $m)) {
                $month = (int) $m[1];
                $day = (int) $m[2];

                if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                    $date = Carbon::createFromDate((int) $referenceDate->year, $month, $day)->startOfDay();
                    if ($date->lt($referenceDate->copy()->startOfDay())) {
                        $date->addYear();
                    }

                    return $date;
                }
            }
        }

        $days = $fallbackDays;
        if ($line->due_mode === ESBTPEcheancierRuleLine::DUE_MODE_DAYS_AFTER_INSCRIPTION) {
            $days = max(0, (int) $line->due_value);
        }

        return $referenceDate->copy()->startOfDay()->addDays($days);
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackLine(
        float $amount,
        Carbon $referenceDate,
        int $fallbackDays,
        string $itemKey,
        ?int $categoryId,
        ?string $categoryName
    ): array {
        return [
            'line_key' => $itemKey . ':line:1',
            'item_key' => $itemKey,
            'label' => 'Échéance unique',
            'category_id' => $categoryId,
            'category_name' => $categoryName,
            'amount' => round(max(0, $amount), 2),
            'due_date' => $referenceDate->copy()->startOfDay()->addDays(max(0, $fallbackDays))->toDateString(),
            'grace_days' => 0,
        ];
    }
}
