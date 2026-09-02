<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;

class SoldesParSouscription
{
    /**
     * @return array{
     *     academic: array{total: float, paid: float, remaining: float, percentage: float, categories: array},
     *     service: array{total: float, paid: float, remaining: float, percentage: float, categories: array},
     *     administrative: array{total: float, paid: float, remaining: float, percentage: float, categories: array},
     *     categories: array<string, array>,
     *     total_paid: float,
     *     total_due: float,
     *     total_remaining: float
     * }
     */
    public function pourInscription(ESBTPInscription $inscription): array
    {
        $soldes = [
            'academic' => ['total' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'percentage' => 0.0, 'categories' => []],
            'service' => ['total' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'percentage' => 0.0, 'categories' => []],
            'administrative' => ['total' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'percentage' => 0.0, 'categories' => []],
        ];

        $souscriptions = ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->with('fraisCategory')
            ->get()
            ->sortBy(fn (ESBTPFraisSubscription $sub) => [
                $sub->fraisCategory->sort_order ?? 9999,
                $sub->fraisCategory->id ?? 0,
            ])
            ->values();

        $paye = ESBTPPaiement::netPaidByCategory($inscription->id, true);
        $categoriesById = [];
        $totalDue = 0.0;
        $totalPaid = 0.0;

        foreach ($souscriptions as $souscription) {
            $category = $souscription->fraisCategory;
            if (! $category) {
                continue;
            }

            if ($souscription->satisfied_in_kind) {
                continue;
            }

            $due = (float) $souscription->chargedAmount();
            if ($souscription->montantNonDefini()) {
                continue;
            }

            $paid = round((float) ($paye[$souscription->frais_category_id] ?? 0), 2);
            $remaining = max(0.0, round($due - $paid, 2));
            $type = $category->category_type ?? 'academic';

            $categoriesById[(string) $category->id] = [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $type,
                'total' => $due,
                'paid' => $paid,
                'remaining' => $remaining,
                'percentage' => $due > 0 ? round(($paid / $due) * 100, 1) : 0,
                'is_mandatory' => (bool) $category->is_mandatory,
                'satisfied_in_kind' => false,
            ];

            $totalDue += $due;
            $totalPaid += min($paid, $due);

            if (isset($soldes[$type])) {
                $soldes[$type]['total'] += $due;
                $soldes[$type]['paid'] += min($paid, $due);
                $soldes[$type]['categories'][] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'amount' => $due,
                    'is_mandatory' => (bool) $category->is_mandatory,
                ];
            }
        }

        foreach ($soldes as $type => &$solde) {
            $solde['remaining'] = max(0.0, (float) $solde['total'] - (float) $solde['paid']);
            $solde['percentage'] = (float) $solde['total'] > 0
                ? round(((float) $solde['paid'] / (float) $solde['total']) * 100, 1)
                : 0;
        }
        unset($solde);

        return array_merge($soldes, [
            'categories' => $categoriesById,
            'total_paid' => round($totalPaid, 2),
            'total_due' => round($totalDue, 2),
            'total_remaining' => max(0.0, round($totalDue - $totalPaid, 2)),
        ]);
    }
}
