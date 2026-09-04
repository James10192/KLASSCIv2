<?php

namespace App\Services\Analytics;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Models\ESBTPInscription;
use App\Services\RelanceCalculationService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CashFlowProjectionService
{
    /** @var array<string, array{data: array<string, float>, computed_at: CarbonImmutable}> */
    private array $cache = [];

    private ?CarbonImmutable $lastComputedAt = null;

    public function __construct(
        private readonly RelanceCalculationService $relanceCalculationService,
        private readonly AnalyticsScanCache $scanCache,
    ) {}

    /**
     * Retourne les recettes attendues par mois sur la base des échéanciers actifs.
     *
     * @return array<string, float>
     */
    public function scheduledRevenueByMonth(AnalyticsContext $context, int $months = 6): array
    {
        $months = max(1, $months);
        $startMonth = now()->startOfMonth();
        $endMonth = now()->addMonths($months)->endOfMonth();

        // Le mois courant fait partie de la clé : la fenêtre glisse avec le
        // calendrier, une entrée d'un mois précédent ne décrit plus la même chose.
        $cacheKey = $context->hash() . ':' . $months . ':' . $startMonth->format('Y-m');

        if (isset($this->cache[$cacheKey])) {
            $this->lastComputedAt = $this->cache[$cacheKey]['computed_at'];

            return $this->cache[$cacheKey]['data'];
        }

        $scan = $this->scanCache->remember(
            'cash_flow_projection',
            $cacheKey,
            fn () => $this->computeScheduledRevenue($context, $startMonth, $endMonth),
        );

        $this->cache[$cacheKey] = ['data' => $scan['data'], 'computed_at' => $scan['computed_at']];
        $this->lastComputedAt = $scan['computed_at'];

        return $scan['data'];
    }

    public function nextMonthRevenue(AnalyticsContext $context): float
    {
        $nextMonthKey = now()->addMonth()->format('Y-m');
        $buckets = $this->scheduledRevenueByMonth($context, 1);

        return (float) ($buckets[$nextMonthKey] ?? 0.0);
    }

    /**
     * Date de calcul de la dernière projection servie. Null tant qu'aucune
     * projection n'a été demandée.
     */
    public function lastComputedAt(): ?CarbonImmutable
    {
        return $this->lastComputedAt;
    }

    /**
     * Balayage intégral des inscriptions de la portée.
     *
     * @return array<string, float>
     */
    private function computeScheduledRevenue(AnalyticsContext $context, Carbon $startMonth, Carbon $endMonth): array
    {
        $buckets = [];

        $this->baseInscriptionQuery($context)
            ->orderBy('id')
            ->chunkById(200, function (Collection $inscriptions) use (&$buckets, $startMonth, $endMonth) {
                $this->relanceCalculationService->preloadForInscriptions($inscriptions);

                foreach ($inscriptions as $inscription) {
                    $state = $this->relanceCalculationService->getFinancialState($inscription);

                    foreach (($state['due_lines'] ?? []) as $line) {
                        $dueDate = Carbon::parse($line['due_date'] ?? now())
                            ->addDays((int) ($line['grace_days'] ?? 0))
                            ->startOfDay();

                        if ($dueDate->lt($startMonth) || $dueDate->gt($endMonth)) {
                            continue;
                        }

                        $key = $dueDate->format('Y-m');
                        // Projection nette : on ne réannonce pas une tranche déjà encaissée.
                        // remaining_amount est posé par EcheancierPaymentAllocationService::allocate().
                        $remaining = $line['remaining_amount'] ?? $line['amount'] ?? 0;
                        $buckets[$key] = ($buckets[$key] ?? 0.0) + (float) $remaining;
                    }
                }
            });

        ksort($buckets);

        return array_map(
            fn (float $value) => round($value, 2),
            $buckets
        );
    }

    private function baseInscriptionQuery(AnalyticsContext $context)
    {
        return ESBTPInscription::query()
            ->with([
                'fraisSubscriptions.selectedOption.assignments',
                'paiements' => fn ($query) => $query->where('status', 'validé')->whereNull('deleted_at'),
            ])
            ->where('workflow_step', 'etudiant_cree')
            ->where('status', 'active')
            ->when($context->anneeId, fn ($query) => $query->where('annee_universitaire_id', $context->anneeId))
            ->when($context->filiereId, fn ($query) => $query->where('filiere_id', $context->filiereId))
            ->when($context->classeId, fn ($query) => $query->where('classe_id', $context->classeId))
            ->when($context->etudiantId, fn ($query) => $query->where('etudiant_id', $context->etudiantId))
            ->when(! $context->anneeId, function ($query) {
                $query->whereHas('anneeUniversitaire', function ($yearQuery) {
                    $yearQuery->where(function ($inner) {
                        $inner->where('is_current', true)
                            ->orWhere('est_actif', true);
                    });
                });
            });
    }
}
