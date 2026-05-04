<?php

namespace App\Services;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionEcheancierSnapshot;
use Carbon\Carbon;

class EcheancierSnapshotService
{
    public function __construct(
        private readonly EcheancierComputationService $computation,
    ) {}

    public function refreshForInscription(ESBTPInscription $inscription): ESBTPInscriptionEcheancierSnapshot
    {
        $inscription->loadMissing([
            'fraisSubscriptions.selectedOption.assignments',
            'paiements' => function ($query) {
                $query->where('status', 'validé')->whereNull('deleted_at');
            },
        ]);

        $categories = ESBTPFraisCategory::query()->where('is_active', true)->get();
        $configurations = ESBTPFraisConfiguration::query()
            ->where('is_active', true)
            ->whereIn('frais_category_id', $categories->pluck('id'))
            ->get()
            ->groupBy(fn ($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $schedule = $this->computation->buildScheduleForInscription(
            $inscription,
            $categories,
            $configurations,
            $inscription->fraisSubscriptions
        );

        $computed = $this->computation->computeOverdueForSchedule(
            $schedule,
            $inscription->paiements,
            Carbon::now()
        );

        $payload = [
            'version' => 1,
            'generated_at' => now()->toDateTimeString(),
            'items' => $computed['items'] ?? [],
            'due_lines' => $computed['due_lines'] ?? [],
            'categories' => $computed['categories'] ?? [],
            'as_of' => $computed['as_of'] ?? now()->toDateString(),
        ];

        return ESBTPInscriptionEcheancierSnapshot::updateOrCreate(
            ['inscription_id' => $inscription->id],
            [
                'snapshot_version' => 1,
                'payload' => $payload,
                'generated_at' => now(),
                'computed_overdue_amount' => (float) ($computed['overdue_amount'] ?? 0),
                'computed_overdue_days' => (int) ($computed['overdue_days'] ?? 0),
                'last_recomputed_at' => now(),
                'metadata' => [
                    'total_due' => (float) ($computed['total_due'] ?? 0),
                    'total_paid_validated' => (float) ($computed['total_paid_validated'] ?? 0),
                    'remaining_total' => (float) ($computed['remaining_total'] ?? 0),
                ],
                'created_by' => auth()->id(),
            ]
        );
    }
}
