<?php

namespace App\Services;

use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EcheancierComputationService
{
    public function __construct(
        private readonly EcheancierResolverService $resolver,
        private readonly EcheancierProjectionService $projection,
        private readonly EcheancierPaymentAllocationService $allocation,
    ) {}

    /**
     * @param Collection<int, ESBTPFraisCategory> $categories
     * @param Collection<string|int, mixed> $configurations
     * @param Collection<int, mixed> $subscriptions
     * @return array{items: array<int, array<string, mixed>>, due_lines: array<int, array<string, mixed>>}
     */
    public function buildScheduleForInscription(
        ESBTPInscription $inscription,
        Collection $categories,
        Collection $configurations,
        Collection $subscriptions
    ): array {
        $items = [];
        $dueLines = [];
        $itemCounter = 0;

        $categoriesById = $categories->keyBy('id');

        foreach ($categories as $category) {
            if (!(bool) $category->is_mandatory) {
                continue;
            }

            $subscription = $subscriptions->firstWhere('frais_category_id', $category->id);
            $configuration = $this->resolveConfiguration($configurations, $category->id, $inscription->filiere_id, $inscription->niveau_id);

            if ($subscription) {
                $amount = (float) $subscription->amount;
                $sourceType = 'subscription_override';
                $sourceId = (int) $subscription->id;
            } else {
                $amount = $configuration
                    ? (float) $configuration->getMontantByStatus($inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS)
                    : (float) ($category->default_amount ?? 0);
                $sourceType = 'configuration';
                $sourceId = $configuration ? (int) $configuration->id : null;
            }

            $amount = round(max(0, $amount), 2);

            $rule = $this->resolver->resolveForConfiguration($configuration, $inscription->affectation_status);
            $ruleLines = $rule ? $rule->lines : collect();
            $fallbackDays = (int) ($configuration->payment_deadline_days ?? $category->payment_deadline_days ?? 30);

            $itemCounter++;
            $itemKey = 'inscription:' . $inscription->id . ':mandatory:' . $category->id . ':' . $itemCounter;

            // amount=0 (gratuit pour ce statut) → on émet quand même un item dans le snapshot
            // pour que la couverture analytics voie la catégorie comme "configurée et gratuite"
            // au lieu de "manquante / fallback". Mais on ne projette aucune tranche
            // (rien à payer = rien à projeter — projectDueLines retourne déjà [] pour amount=0).
            $projectedLines = $amount > 0
                ? $this->projection->projectDueLines(
                    $amount,
                    $ruleLines,
                    $inscription->date_inscription
                        ? Carbon::parse($inscription->date_inscription)
                        : Carbon::parse($inscription->created_at ?? now()),
                    $fallbackDays,
                    $itemKey,
                    (int) $category->id,
                    (string) $category->name
                )
                : [];

            $items[] = [
                'item_key' => $itemKey,
                'label' => (string) $category->name,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'category_id' => (int) $category->id,
                'category_name' => (string) $category->name,
                'amount' => $amount,
                'rule_id' => $rule?->id,
                'is_free' => $amount === 0.0,
            ];

            $dueLines = array_merge($dueLines, $projectedLines);
        }

        $optionalSubscriptions = $subscriptions->filter(function ($subscription) use ($categoriesById) {
            $category = $categoriesById->get($subscription->frais_category_id);
            return $category && !(bool) $category->is_mandatory && (bool) $subscription->is_active;
        })->values();

        foreach ($optionalSubscriptions as $subscription) {
            $category = $categoriesById->get($subscription->frais_category_id);
            if (!$category) {
                continue;
            }

            $amount = round(max(0, (float) $subscription->amount), 2);

            $option = $subscription->relationLoaded('selectedOption')
                ? $subscription->selectedOption
                : $subscription->selectedOption()->with('assignments')->first();

            $assignment = $this->resolver->findBestAssignmentForInscription($option, $inscription);
            $rule = $this->resolver->resolveForOptionAssignment($assignment, $inscription->affectation_status);
            $ruleLines = $rule ? $rule->lines : collect();
            $fallbackDays = (int) ($category->payment_deadline_days ?? 30);

            $itemCounter++;
            $itemKey = 'inscription:' . $inscription->id . ':optional:' . $subscription->id . ':' . $itemCounter;

            // Cf bloc mandatory ci-dessus : amount=0 = subscription gratuite, on émet
            // l'item dans le snapshot mais sans tranche à projeter.
            $projectedLines = $amount > 0
                ? $this->projection->projectDueLines(
                    $amount,
                    $ruleLines,
                    $subscription->subscribed_at
                        ? Carbon::parse($subscription->subscribed_at)
                        : Carbon::parse($inscription->created_at ?? now()),
                    $fallbackDays,
                    $itemKey,
                    (int) $category->id,
                    (string) $category->name
                )
                : [];

            $items[] = [
                'item_key' => $itemKey,
                'label' => (string) $category->name . ($option ? ' - ' . $option->name : ''),
                'source_type' => 'subscription',
                'source_id' => (int) $subscription->id,
                'category_id' => (int) $category->id,
                'category_name' => (string) $category->name,
                'amount' => $amount,
                'rule_id' => $rule?->id,
                'option_assignment_id' => $assignment?->id,
                'is_free' => $amount === 0.0,
            ];

            $dueLines = array_merge($dueLines, $projectedLines);
        }

        return [
            'items' => $items,
            'due_lines' => $dueLines,
        ];
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, due_lines: array<int, array<string, mixed>>} $schedule
     * @return array<string, mixed>
     */
    public function computeOverdueForSchedule(array $schedule, Collection $validatedPayments, ?Carbon $asOf = null): array
    {
        $asOf = $asOf ?: now();
        $dueLines = $schedule['due_lines'] ?? [];

        $allocatedLines = $this->allocation->allocate($dueLines, $validatedPayments);

        $totalDue = round(collect($allocatedLines)->sum(fn ($line) => (float) ($line['amount'] ?? 0)), 2);
        $totalPaidOnSchedule = round(collect($allocatedLines)->sum(fn ($line) => (float) ($line['paid_amount'] ?? 0)), 2);
        $totalPaidValidated = round((float) $validatedPayments->sum('montant'), 2);
        $remainingTotal = round(max(0, $totalDue - $totalPaidOnSchedule), 2);

        $expectedDueToDate = 0.0;
        $paidDueToDate = 0.0;
        $oldestOverdueDate = null;

        foreach ($allocatedLines as $line) {
            $dueWithGrace = Carbon::parse($line['due_date'])->addDays((int) ($line['grace_days'] ?? 0))->startOfDay();
            $line['due_date_with_grace'] = $dueWithGrace->toDateString();

            $amount = (float) ($line['amount'] ?? 0);
            $paid = (float) ($line['paid_amount'] ?? 0);
            $remaining = (float) ($line['remaining_amount'] ?? 0);

            if ($dueWithGrace->lte($asOf->copy()->startOfDay())) {
                $expectedDueToDate += $amount;
                $paidDueToDate += min($amount, $paid);

                if ($remaining > 0) {
                    if ($oldestOverdueDate === null || $dueWithGrace->lt($oldestOverdueDate)) {
                        $oldestOverdueDate = $dueWithGrace->copy();
                    }
                }
            }
        }

        $expectedDueToDate = round($expectedDueToDate, 2);
        $paidDueToDate = round($paidDueToDate, 2);
        $overdueAmount = round(max(0, $expectedDueToDate - $paidDueToDate), 2);
        $overdueDays = $overdueAmount > 0 && $oldestOverdueDate
            ? max(0, $oldestOverdueDate->diffInDays($asOf, false))
            : 0;

        $categoriesSummary = [];
        foreach ($allocatedLines as $line) {
            $categoryId = $line['category_id'] ?? 'global';
            $bucketKey = (string) $categoryId;

            if (!isset($categoriesSummary[$bucketKey])) {
                $categoriesSummary[$bucketKey] = [
                    'category_id' => $categoryId,
                    'category_name' => $line['category_name'] ?? 'Global',
                    'total_due' => 0.0,
                    'total_paid' => 0.0,
                    'total_remaining' => 0.0,
                ];
            }

            $categoriesSummary[$bucketKey]['total_due'] += (float) ($line['amount'] ?? 0);
            $categoriesSummary[$bucketKey]['total_paid'] += (float) ($line['paid_amount'] ?? 0);
            $categoriesSummary[$bucketKey]['total_remaining'] += (float) ($line['remaining_amount'] ?? 0);
        }

        $categoriesSummary = array_values(array_map(function ($category) {
            $category['total_due'] = round($category['total_due'], 2);
            $category['total_paid'] = round($category['total_paid'], 2);
            $category['total_remaining'] = round($category['total_remaining'], 2);
            $category['coverage_rate'] = $category['total_due'] > 0
                ? round(min(100, ($category['total_paid'] / $category['total_due']) * 100), 2)
                : 100.0;

            return $category;
        }, $categoriesSummary));

        return [
            'items' => $schedule['items'] ?? [],
            'due_lines' => $allocatedLines,
            'total_due' => $totalDue,
            'total_paid' => $totalPaidOnSchedule,
            'total_paid_validated' => $totalPaidValidated,
            'remaining_total' => $remainingTotal,
            'expected_due_to_date' => $expectedDueToDate,
            'paid_due_to_date' => $paidDueToDate,
            'overdue_amount' => $overdueAmount,
            'overdue_days' => $overdueDays,
            'is_overdue' => $overdueAmount > 0,
            'overdue_since' => $oldestOverdueDate?->toDateString(),
            'categories' => $categoriesSummary,
            'as_of' => $asOf->toDateString(),
        ];
    }

    private function resolveConfiguration(Collection $configurations, int $categoryId, int $filiereId, int $niveauId): ?ESBTPFraisConfiguration
    {
        $key = $categoryId . '_' . $filiereId . '_' . $niveauId;

        if ($configurations->has($key)) {
            $group = $configurations->get($key);
            if ($group instanceof Collection) {
                return $this->pickValidConfiguration($group);
            }
            if ($group instanceof ESBTPFraisConfiguration) {
                return $group;
            }
        }

        // Fallback flat-collection scan : ne s'applique que si le caller passe
        // une collection NON groupée. RelanceCalculationService et
        // EcheancierSnapshotService passent toujours du groupBy, donc on est
        // safe — mais on défend contre un appelant futur.
        $matches = $configurations->filter(function ($configuration) use ($categoryId, $filiereId, $niveauId) {
            if (! $configuration instanceof ESBTPFraisConfiguration) {
                return false;
            }

            return (int) $configuration->frais_category_id === $categoryId
                && (int) $configuration->filiere_id === $filiereId
                && (int) $configuration->niveau_id === $niveauId;
        });

        if ($matches->isEmpty()) {
            return null;
        }

        return $this->pickValidConfiguration($matches);
    }

    private function pickValidConfiguration(Collection $configurations): ?ESBTPFraisConfiguration
    {
        $today = now()->toDateString();

        $valid = $configurations->filter(function ($configuration) use ($today) {
            if (!(bool) $configuration->is_active) {
                return false;
            }

            $effectiveOk = empty($configuration->effective_date) || (string) $configuration->effective_date <= $today;
            $expiryOk = empty($configuration->expiry_date) || (string) $configuration->expiry_date >= $today;

            return $effectiveOk && $expiryOk;
        });

        if ($valid->isEmpty()) {
            return $configurations->first();
        }

        return $valid->sortByDesc('effective_date')->first();
    }
}
