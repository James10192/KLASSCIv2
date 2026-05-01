<?php

namespace App\Actions\Comptabilite;

use App\DTOs\Comptabilite\ComptabiliteFilters;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Construit le payload du dashboard comptabilité (KPIs + encaissements + aging + pending).
 * Utilisé par dashboard() (HTML) et dashboardData() (JSON AJAX) — élimine la duplication.
 */
class BuildDashboardDataAction
{
    private const PAYMENT_STATUS_VALIDATED = 'validé';
    private const PAYMENT_STATUS_PENDING = 'en_attente';
    private const ACTIVE_INSCRIPTION_STATUSES = ['active', 'en_attente', 'validée'];
    private const PENDING_PAYMENTS_LIMIT = 10;

    public function __construct(
        private readonly GetImpayesAgingAction $getImpayesAging,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(ComptabiliteFilters $filters, ?ESBTPAnneeUniversitaire $annee): array
    {
        $totalPaid = (float) $this->paiementsQuery($filters)
            ->where('status', self::PAYMENT_STATUS_VALIDATED)
            ->sum('montant');

        $totalDuResult = $this->getImpayesAging->totalDuForFilters($filters);
        $totalOverdue = max(0.0, $totalDuResult['totalDue'] - $totalPaid);

        $countPaid = $this->paiementsQuery($filters)
            ->where('status', self::PAYMENT_STATUS_VALIDATED)
            ->count();
        $countPartiallyPaid = $this->paiementsQuery($filters)
            ->where('status', self::PAYMENT_STATUS_PENDING)
            ->count();
        $countOverdue = ESBTPInscription::query()
            ->when($filters->anneeId, fn ($q) => $q->where('annee_universitaire_id', $filters->anneeId))
            ->when($filters->filiereId, fn ($q) => $q->whereHas('classe', fn ($q2) => $q2->where('filiere_id', $filters->filiereId)))
            ->when($filters->classeId, fn ($q) => $q->where('classe_id', $filters->classeId))
            ->whereIn('status', self::ACTIVE_INSCRIPTION_STATUSES)
            ->count();

        [$labelsMois, $dataEncaissements] = $this->buildMonthlySeries($filters, $annee);

        return [
            'totalDue' => $totalDuResult['totalDue'],
            'countDue' => $totalDuResult['countDue'],
            'totalPaid' => $totalPaid,
            'totalOverdue' => $totalOverdue,
            'countPaid' => $countPaid,
            'countPartiallyPaid' => $countPartiallyPaid,
            'countOverdue' => $countOverdue,
            'labelsMois' => $labelsMois,
            'dataEncaissements' => $dataEncaissements,
            'agingBuckets' => ($this->getImpayesAging)($filters),
            'paiementsEnAttente' => $this->fetchPendingPayments($filters),
            // Module Dépenses supprimé — données vides pour compat backward
            'statsDepenses' => ['total' => 0, 'mensuel' => 0, 'salaires' => 0, 'fournitures' => 0],
            'labelsMoisDepenses' => $labelsMois,
            'dataDepensesMensuelles' => array_fill(0, count($labelsMois), 0),
        ];
    }

    private function paiementsQuery(ComptabiliteFilters $filters): Builder
    {
        return ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->when($filters->anneeId, fn ($q) => $q->whereHas('inscription', fn ($q2) => $q2->where('annee_universitaire_id', $filters->anneeId)))
            ->when($filters->filiereId, fn ($q) => $q->whereHas('inscription.classe', fn ($q2) => $q2->where('filiere_id', $filters->filiereId)))
            ->when($filters->classeId, fn ($q) => $q->whereHas('inscription', fn ($q2) => $q2->where('classe_id', $filters->classeId)));
    }

    /**
     * Single GROUP BY query au lieu de N queries (1 par mois) — évite N+1 sur dashboard load.
     *
     * @return array{0: array<int, string>, 1: array<int, float>}
     */
    private function buildMonthlySeries(ComptabiliteFilters $filters, ?ESBTPAnneeUniversitaire $annee): array
    {
        if (!$annee || !$annee->start_date) {
            return [[], []];
        }

        $debut = Carbon::parse($annee->start_date);
        $fin = Carbon::parse($annee->end_date ?? now());

        $monthlyTotals = $this->paiementsQuery($filters)
            ->where('status', self::PAYMENT_STATUS_VALIDATED)
            ->whereBetween('date_paiement', [$debut, $fin])
            ->selectRaw('YEAR(date_paiement) as y, MONTH(date_paiement) as m, SUM(montant) as total')
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn ($row) => $row->y . '-' . str_pad((string) $row->m, 2, '0', STR_PAD_LEFT));

        $labels = [];
        $data = [];
        for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
            $labels[] = $date->translatedFormat('M Y');
            $data[] = (float) ($monthlyTotals->get($date->format('Y-m'))?->total ?? 0);
        }

        return [$labels, $data];
    }

    private function fetchPendingPayments(ComptabiliteFilters $filters): EloquentCollection
    {
        return ESBTPPaiement::query()
            ->with(['inscription.etudiant', 'fraisCategory'])
            ->where('status', self::PAYMENT_STATUS_PENDING)
            ->whereNull('deleted_at')
            ->when($filters->anneeId, fn ($q) => $q->whereHas('inscription', fn ($q2) => $q2->where('annee_universitaire_id', $filters->anneeId)))
            ->orderByDesc('created_at')
            ->limit(self::PENDING_PAYMENTS_LIMIT)
            ->get();
    }

    /**
     * Sérialise vers la shape JSON attendue par dashboardData() (filtres AJAX).
     */
    public static function pendingPaymentsToArray(EloquentCollection $payments): Collection
    {
        return $payments->map(fn ($p) => [
            'nom' => $p->inscription->etudiant->nom ?? 'N/A',
            'prenoms' => $p->inscription->etudiant->prenoms ?? '',
            'categorie' => $p->fraisCategory->name ?? $p->motif ?? '—',
            'montant' => (float) $p->montant,
            'date' => Carbon::parse($p->date_paiement)->format('d/m/Y'),
            'url' => route('esbtp.paiements.show', $p->id),
        ]);
    }
}
