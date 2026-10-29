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

        // Étape 1 — pré-allouer les paiements TARGETED (target_due_line_key non null)
        // directement sur la tranche cible, avant tout pool catégorie. Permet à un
        // utilisateur qui paye explicitement la "tranche 3" de la voir affectée à la
        // tranche 3 et non absorbée FIFO sur les tranches précédentes.
        $linesByKey = [];
        foreach ($dueLines as $i => $line) {
            $linesByKey[(string) ($line['line_key'] ?? '')] = $i;
        }
        $preAllocated = array_fill(0, count($dueLines), 0.0);

        // On construit le pool standard sous forme [category_key => sum] sans cloner d'Eloquent
        // models. Le surplus d'un paiement targeted (montant > tranche cible) bascule dans ce
        // pool comme amount résiduel.
        $pools = [];

        foreach ($validatedPayments as $payment) {
            $catKey = $payment->frais_category_id !== null ? (string) $payment->frais_category_id : 'global';
            $amount = (float) $payment->montant;
            $target = (string) ($payment->target_due_line_key ?? '');

            if ($target !== '' && isset($linesByKey[$target])) {
                $idx = $linesByKey[$target];
                $lineAmount = round(max(0, (float) ($dueLines[$idx]['amount'] ?? 0)), 2);
                $available = max(0, $lineAmount - $preAllocated[$idx]);
                $alloc = round(min($available, $amount), 2);
                $preAllocated[$idx] += $alloc;
                $surplus = round(max(0, $amount - $alloc), 2);
                if ($surplus > 0.01) {
                    $pools[$catKey] = ($pools[$catKey] ?? 0.0) + $surplus;
                }
                continue;
            }

            $pools[$catKey] = ($pools[$catKey] ?? 0.0) + $amount;
        }

        // Index original préservé pour rendre les pré-allocations
        $sortedIndexes = array_keys($dueLines);
        usort($sortedIndexes, function ($a, $b) use ($dueLines) {
            $aDate = (string) ($dueLines[$a]['due_date'] ?? '9999-12-31');
            $bDate = (string) ($dueLines[$b]['due_date'] ?? '9999-12-31');
            if ($aDate !== $bDate) {
                return $aDate <=> $bDate;
            }
            return (string) ($dueLines[$a]['line_key'] ?? '') <=> (string) ($dueLines[$b]['line_key'] ?? '');
        });

        $allocated = array_fill(0, count($dueLines), null);
        foreach ($sortedIndexes as $idx) {
            $line = $dueLines[$idx];
            $categoryKey = isset($line['category_id']) && $line['category_id'] !== null
                ? (string) $line['category_id']
                : 'global';

            $amount = round(max(0, (float) ($line['amount'] ?? 0)), 2);
            $pre = round($preAllocated[$idx], 2);
            $remainingNeed = round(max(0, $amount - $pre), 2);

            $pool = round(max(0, (float) ($pools[$categoryKey] ?? 0)), 2);
            $fromPool = round(min($remainingNeed, $pool), 2);
            $pools[$categoryKey] = round(max(0, $pool - $fromPool), 2);

            $paid = round($pre + $fromPool, 2);
            $remaining = round(max(0, $amount - $paid), 2);

            $line['paid_amount'] = $paid;
            $line['remaining_amount'] = $remaining;
            $allocated[$idx] = $line;
        }

        return array_values($allocated);
    }
}
