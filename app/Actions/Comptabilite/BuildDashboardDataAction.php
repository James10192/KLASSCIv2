<?php

namespace App\Actions\Comptabilite;

use App\DTOs\Comptabilite\ComptabiliteFilters;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Construit le payload de données du dashboard comptabilité (KPIs paiements,
 * encaissements mensuels, aging buckets, paiements en attente). Utilisé par
 * dashboard() (HTML) et dashboardData() (JSON AJAX) — élimine la duplication.
 */
class BuildDashboardDataAction
{
    private const PAYMENT_STATUS_VALIDATED = 'validé';
    private const PAYMENT_STATUS_PENDING = 'en_attente';
    private const ACTIVE_INSCRIPTION_STATUSES = ['active', 'en_attente', 'validée'];
    private const PENDING_PAYMENTS_LIMIT = 10;

    public function __construct(
        private readonly CalculerTotalDuAction $calculerTotalDu,
        private readonly GetImpayesAgingAction $getImpayesAging,
    ) {}

    public function __invoke(ComptabiliteFilters $filters, ?ESBTPAnneeUniversitaire $annee): array
    {
        $totalPaid = (float) $this->paiementsQuery($filters)
            ->where('status', self::PAYMENT_STATUS_VALIDATED)
            ->sum('montant');

        $totalDuResult = ($this->calculerTotalDu)($filters);
        $totalOverdue = max(0.0, $totalDuResult->totalDue - $totalPaid);

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
            'totalDue' => $totalDuResult->totalDue,
            'countDue' => $totalDuResult->countDue,
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
     * @return array{0: array<int, string>, 1: array<int, float>}
     */
    private function buildMonthlySeries(ComptabiliteFilters $filters, ?ESBTPAnneeUniversitaire $annee): array
    {
        if (!$annee || !$annee->start_date) {
            return [[], []];
        }

        $debut = Carbon::parse($annee->start_date);
        $fin = Carbon::parse($annee->end_date ?? now());

        $labels = [];
        $data = [];
        for ($date = $debut->copy(); $date->lte($fin); $date->addMonth()) {
            $labels[] = $date->translatedFormat('M Y');
            $data[] = (float) $this->paiementsQuery($filters)
                ->where('status', self::PAYMENT_STATUS_VALIDATED)
                ->whereMonth('date_paiement', $date->month)
                ->whereYear('date_paiement', $date->year)
                ->sum('montant');
        }

        return [$labels, $data];
    }

    /**
     * Retourne les ESBTPPaiement Eloquent objects (consommés par la vue Blade).
     * Pour le JSON AJAX, le controller appelle pendingPaymentsToArray() ci-dessous.
     */
    private function fetchPendingPayments(ComptabiliteFilters $filters): \Illuminate\Database\Eloquent\Collection
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
     * Sérialise la Collection<ESBTPPaiement> vers la shape JSON attendue par
     * dashboardData() (utilisé par les filtres AJAX du dashboard).
     */
    public static function pendingPaymentsToArray(\Illuminate\Database\Eloquent\Collection $payments): \Illuminate\Support\Collection
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
