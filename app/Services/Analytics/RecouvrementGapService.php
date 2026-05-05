<?php

namespace App\Services\Analytics;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Models\ESBTPInscription;
use App\Services\RelanceCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Compare, mois par mois, le montant attendu via les échéanciers actifs au
 * montant effectivement encaissé (allocations issues de
 * EcheancierPaymentAllocationService). Sert d'entrée à la détection
 * recouvrement_gap dans AnomalyDetector.
 */
class RecouvrementGapService
{
    /** @var array<string, array<string, array{expected: float, paid: float, gap: float, gap_ratio: float}>> */
    private array $cache = [];

    public function __construct(
        private readonly RelanceCalculationService $relanceCalculationService,
    ) {}

    /**
     * Buckets mensuels pour les `$pastMonths` derniers mois clos
     * (n'inclut PAS le mois en cours, qui n'est pas encore terminé).
     *
     * @return array<string, array{expected: float, paid: float, gap: float, gap_ratio: float}>
     */
    public function monthlyGaps(AnalyticsContext $context, int $pastMonths = 3): array
    {
        $pastMonths = max(1, $pastMonths);
        $cacheKey = $context->hash() . ':' . $pastMonths;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        $startMonth = now()->subMonthsNoOverflow($pastMonths)->startOfMonth();
        $endMonth = now()->startOfMonth()->subDay();

        if ($endMonth->lt($startMonth)) {
            return [];
        }

        $buckets = [];

        $this->baseInscriptionQuery($context)
            ->orderBy('id')
            ->chunkById(200, function (Collection $inscriptions) use (&$buckets, $startMonth, $endMonth) {
                $this->relanceCalculationService->preloadForInscriptions($inscriptions);

                foreach ($inscriptions as $inscription) {
                    $state = $this->relanceCalculationService->getFinancialState($inscription);
                    $this->collectLines($state['due_lines'] ?? [], $startMonth, $endMonth, $buckets);
                }
            });

        ksort($buckets);

        $result = array_map(fn (array $bucket) => $this->summarize($bucket), $buckets);
        $this->cache[$cacheKey] = $result;

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $dueLines
     * @param  array<string, array{expected: float, paid: float}>  $buckets
     */
    private function collectLines(array $dueLines, Carbon $startMonth, Carbon $endMonth, array &$buckets): void
    {
        foreach ($dueLines as $line) {
            $dueDate = Carbon::parse($line['due_date'] ?? now())
                ->addDays((int) ($line['grace_days'] ?? 0))
                ->startOfDay();

            if ($dueDate->lt($startMonth) || $dueDate->gt($endMonth)) {
                continue;
            }

            $key = $dueDate->format('Y-m');
            $expected = (float) ($line['amount'] ?? 0);
            $paid = (float) ($line['paid_amount'] ?? 0);

            if (!isset($buckets[$key])) {
                $buckets[$key] = ['expected' => 0.0, 'paid' => 0.0];
            }

            $buckets[$key]['expected'] += $expected;
            $buckets[$key]['paid'] += min($expected, $paid);
        }
    }

    /**
     * @param  array{expected: float, paid: float}  $bucket
     * @return array{expected: float, paid: float, gap: float, gap_ratio: float}
     */
    private function summarize(array $bucket): array
    {
        $expected = round($bucket['expected'], 2);
        $paid = round($bucket['paid'], 2);
        $gap = round(max(0.0, $expected - $paid), 2);
        $gapRatio = $expected > 0 ? round($gap / $expected, 4) : 0.0;

        return [
            'expected' => $expected,
            'paid' => $paid,
            'gap' => $gap,
            'gap_ratio' => $gapRatio,
        ];
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
