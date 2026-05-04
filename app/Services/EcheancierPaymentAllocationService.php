<?php

namespace App\Services;

use Illuminate\Support\Collection;

class EcheancierPaymentAllocationService
{
    /**
     * @param array<int, array<string, mixed>> $dueLines
     * @return array<int, array<string, mixed>>
     */
    public function allocate(array $dueLines, Collection $validatedPayments): array
    {
        if (empty($dueLines)) {
            return [];
        }

        $pools = [];
        foreach ($validatedPayments as $payment) {
            $key = $payment->frais_category_id !== null ? (string) $payment->frais_category_id : 'global';
            $pools[$key] = ($pools[$key] ?? 0.0) + (float) $payment->montant;
        }

        usort($dueLines, function ($a, $b) {
            $aDate = (string) ($a['due_date'] ?? '9999-12-31');
            $bDate = (string) ($b['due_date'] ?? '9999-12-31');
            if ($aDate !== $bDate) {
                return $aDate <=> $bDate;
            }

            return (string) ($a['line_key'] ?? '') <=> (string) ($b['line_key'] ?? '');
        });

        $allocated = [];
        foreach ($dueLines as $line) {
            $categoryKey = isset($line['category_id']) && $line['category_id'] !== null
                ? (string) $line['category_id']
                : 'global';

            $amount = round(max(0, (float) ($line['amount'] ?? 0)), 2);
            $pool = round(max(0, (float) ($pools[$categoryKey] ?? 0)), 2);

            $paid = round(min($amount, $pool), 2);
            $remaining = round(max(0, $amount - $paid), 2);

            $pools[$categoryKey] = round(max(0, $pool - $paid), 2);

            $line['paid_amount'] = $paid;
            $line['remaining_amount'] = $remaining;
            $allocated[] = $line;
        }

        return $allocated;
    }
}
