<?php

namespace App\Actions\Comptabilite;

use App\DTOs\Comptabilite\ComptabiliteFilters;
use App\Enums\ModePaiement;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Construit le payload du dashboard comptabilité (KPIs + encaissements + aging + pending).
 * Utilisé par dashboard() (HTML) et dashboardData() (JSON AJAX) — élimine la duplication.
 */
class BuildDashboardDataAction
{
    private const PAYMENT_STATUS_VALIDATED = 'validé';
    private const PAYMENT_STATUS_PENDING = 'en_attente';
    private const PENDING_PAYMENTS_LIMIT = 10;
    private const SERIE_JOURS = 84;
    private const CLASSES_AFFICHEES = 6;

    /**
     * Audit 2026-06-04 §2.1 : page dashboard observée à 33s sur yakro (1561 inscriptions
     * × calcul échéancier par inscription via RelanceCalculationService).
     * Cache 60s = compromis entre fraicheur et performance UI.
     * Invalidation : via Cache::forget('dashboard_compta_*') ou cache:clear.
     */
    private const CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly GetImpayesAgingAction $getImpayesAging,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(ComptabiliteFilters $filters, ?ESBTPAnneeUniversitaire $annee): array
    {
        $cacheKey = sprintf(
            'dashboard_compta_v2_%s_%s_%s_%s',
            $filters->anneeId ?? 'all',
            $filters->filiereId ?? 'all',
            $filters->classeId ?? 'all',
            $annee?->id ?? 'none',
        );

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($filters, $annee) {
            return $this->build($filters, $annee);
        });
    }

    /**
     * Les chiffres de l'année en cours, sans filtre de filière ni de classe :
     * ceux de l'accueil comptable. Même clé de cache que l'analyse financière
     * ouverte sans filtre, donc les deux écrans lisent littéralement le même
     * calcul.
     *
     * @return array<string, mixed>
     */
    public function pourAnnee(?ESBTPAnneeUniversitaire $annee): array
    {
        $filtres = $annee
            ? ComptabiliteFilters::empty()->withAnneeDefault((int) $annee->id)
            : ComptabiliteFilters::empty();

        return $this($filtres, $annee);
    }

    /**
     * @return array<string, mixed>
     */
    private function build(ComptabiliteFilters $filters, ?ESBTPAnneeUniversitaire $annee): array
    {
        $statusAgg = $this->paiementsQuery($filters)
            ->selectRaw(
                'COUNT(*) as total_count,
                SUM(CASE WHEN status = ? THEN ('.ESBTPPaiement::sqlCashCase().') ELSE 0 END) as total_paid,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as count_paid,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as count_pending,
                SUM(CASE WHEN status = ? AND (nature IS NULL OR nature = \'encaissement\') THEN montant ELSE 0 END) as total_pending',
                [
                    self::PAYMENT_STATUS_VALIDATED,
                    self::PAYMENT_STATUS_VALIDATED,
                    self::PAYMENT_STATUS_PENDING,
                    self::PAYMENT_STATUS_PENDING,
                ]
            )
            ->first();
        $totalPaid = (float) ($statusAgg->total_paid ?? 0);

        // Un seul passage : dû, ancienneté et plus gros impayés viennent du même
        // calcul par inscription (revue avant fusion, septembre 2026).
        $analyse = $this->getImpayesAging->analyse($filters);
        $totalDuResult = ['totalDue' => $analyse['totalDue'], 'countDue' => $analyse['countDue'], 'parClasse' => $analyse['parClasse']];
        $agingBuckets = $analyse['buckets'];
        $countOverdueTotal = (int) array_sum(array_column($agingBuckets, 'count'));
        $totalOverdue = (float) array_sum(array_column($agingBuckets, 'amount'));

        $countPaid = (int) ($statusAgg->count_paid ?? 0);
        $countPartiallyPaid = (int) ($statusAgg->count_pending ?? 0);

        // « Aujourd'hui » = date de paiement, la date de la caisse. La date de
        // validation donnait un autre montant que l'accueil pour la même
        // question (revue adverse, septembre 2026).
        $aujourdhui = $this->netEntre($filters, Carbon::today(), Carbon::today());
        $hier = $this->netEntre($filters, Carbon::yesterday(), Carbon::yesterday());
        $mois = $this->netEntre($filters, Carbon::today()->startOfMonth(), Carbon::today());
        // Le mois précédent à la même date : un mois entamé face à un mois
        // complet baisserait toujours.
        $moisPrecedent = $this->netEntre(
            $filters,
            Carbon::today()->subMonthNoOverflow()->startOfMonth(),
            Carbon::today()->subMonthNoOverflow()
        );

        [$labelsMois, $dataEncaissements] = $this->buildMonthlySeries($filters, $annee);
        [$labelPrecedente, $dataPrecedente] = $this->serieAnneePrecedente($filters, $annee, count($labelsMois));

        return [
            'totalDue' => $totalDuResult['totalDue'],
            'countDue' => $totalDuResult['countDue'],
            'totalPaid' => $totalPaid,
            'totalOverdue' => $totalOverdue,
            'totalPending' => round((float) ($statusAgg->total_pending ?? 0), 2),
            'countPaid' => $countPaid,
            'countPartiallyPaid' => $countPartiallyPaid,
            'countOverdue' => $countOverdueTotal,
            'countToValidate' => $countPartiallyPaid,
            'countOverdueTotal' => $countOverdueTotal,
            'countValidatedToday' => $aujourdhui['count'],
            'totalValidatedToday' => $aujourdhui['total'],
            'totalPaidYesterday' => $hier['total'],
            'totalPaidMonth' => $mois['total'],
            'totalPaidPrevMonthToDate' => $moisPrecedent['total'],
            'labelsMois' => $labelsMois,
            'dataEncaissements' => $dataEncaissements,
            'labelAnneePrecedente' => $labelPrecedente,
            'dataEncaissementsPrecedente' => $dataPrecedente,
            'serieJours' => $this->serieJours($filters, self::SERIE_JOURS),
            'modes' => $this->parMode($filters, null),
            'modesMois' => $this->parMode($filters, Carbon::today()->startOfMonth()),
            'recouvrementParClasse' => $this->recouvrementParClasse($filters, $totalDuResult['parClasse'], $analyse['inscriptionsActives']),
            'topEchus' => $analyse['top'],
            'agingBuckets' => $agingBuckets,
            'paiementsEnAttente' => $this->fetchPendingPayments($filters),
            // Module Dépenses supprimé — données vides pour compat backward
            'statsDepenses' => ['total' => 0, 'mensuel' => 0, 'salaires' => 0, 'fournitures' => 0],
            'labelsMoisDepenses' => $labelsMois,
            'dataDepensesMensuelles' => array_fill(0, count($labelsMois), 0),
        ];
    }

    /**
     * @return array{count: int, total: float}
     */
    private function netEntre(ComptabiliteFilters $filters, Carbon $debut, Carbon $fin): array
    {
        $ligne = $this->paiementsQuery($filters)
            ->where('status', self::PAYMENT_STATUS_VALIDATED)
            ->whereDate('date_paiement', '>=', $debut->toDateString())
            ->whereDate('date_paiement', '<=', $fin->toDateString())
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM('.ESBTPPaiement::sqlCashCase().'), 0) as total')
            ->first();

        return ['count' => (int) ($ligne->cnt ?? 0), 'total' => round((float) ($ligne->total ?? 0), 2)];
    }

    /**
     * Encaissé net par jour sur les N derniers jours, aujourd'hui compris.
     * Un jour sans encaissement vaut 0 : c'est une valeur.
     *
     * @return array<int, array{jour: string, total: float}>
     */
    private function serieJours(ComptabiliteFilters $filters, int $jours): array
    {
        $debut = Carbon::today()->subDays($jours - 1);
        $totaux = $this->paiementsQuery($filters)
            ->where('status', self::PAYMENT_STATUS_VALIDATED)
            ->whereDate('date_paiement', '>=', $debut->toDateString())
            ->selectRaw('DATE(date_paiement) as jour, SUM('.ESBTPPaiement::sqlCashCase().') as total')
            ->groupBy('jour')
            ->pluck('total', 'jour');

        $serie = [];
        for ($d = $debut->copy(); $d->lte(Carbon::today()); $d->addDay()) {
            $serie[] = ['jour' => $d->toDateString(), 'total' => round((float) ($totaux[$d->toDateString()] ?? 0), 2)];
        }

        return $serie;
    }

    /**
     * @return array<int, array{mode: string, count: int, total: float}>
     */
    private function parMode(ComptabiliteFilters $filters, ?Carbon $depuis): array
    {
        return $this->paiementsQuery($filters)
            ->where('status', self::PAYMENT_STATUS_VALIDATED)
            ->when($depuis, fn ($q) => $q->whereDate('date_paiement', '>=', $depuis->toDateString()))
            ->groupBy('mode_paiement')
            ->selectRaw('mode_paiement, COUNT(*) as cnt, SUM('.ESBTPPaiement::sqlCashCase().') as total')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($l) => [
                'mode' => ModePaiement::fromLegacy((string) $l->mode_paiement)?->label() ?? ((string) $l->mode_paiement ?: 'Non précisé'),
                'count' => (int) $l->cnt,
                'total' => round((float) $l->total, 2),
            ])
            ->values()
            ->all();
    }

    /**
     * Taux par classe, les moins recouvrées d'abord : c'est là qu'il faut agir.
     * Le dû vient du même calcul que le total dû (RelanceCalculationService).
     *
     * Le payé ne compte que les inscriptions dont le dû est compté (actives) :
     * sinon le versement d'une inscription abandonnée gonflait le taux.
     *
     * @param  array<int, float>  $duParClasse
     * @param  array<int, int>  $inscriptionsActives
     * @return array<int, array{classe_id: int, classe: string, du: float, paye: float, taux: float}>
     */
    private function recouvrementParClasse(ComptabiliteFilters $filters, array $duParClasse, array $inscriptionsActives): array
    {
        if ($duParClasse === [] || $inscriptionsActives === []) {
            return [];
        }

        // Par inscription puis ramené à la classe : une jointure rendrait
        // ambiguës les colonnes que les deux tables partagent (status, deleted_at).
        $payeParInscription = $this->paiementsQuery($filters)
            ->where('status', self::PAYMENT_STATUS_VALIDATED)
            ->whereIn('inscription_id', $inscriptionsActives)
            ->groupBy('inscription_id')
            ->selectRaw('inscription_id, SUM('.ESBTPPaiement::sqlCashCase().') as total')
            ->pluck('total', 'inscription_id');
        $classeDe = ESBTPInscription::query()
            ->whereIn('id', $payeParInscription->keys())
            ->pluck('classe_id', 'id');
        $payeParClasse = [];
        foreach ($payeParInscription as $inscriptionId => $total) {
            $cle = (int) ($classeDe[$inscriptionId] ?? 0);
            $payeParClasse[$cle] = ($payeParClasse[$cle] ?? 0.0) + (float) $total;
        }

        $noms = ESBTPClasse::query()->whereIn('id', array_keys($duParClasse))->pluck('name', 'id');

        $lignes = [];
        foreach ($duParClasse as $classeId => $du) {
            if ($du <= 0) {
                continue;
            }
            $paye = (float) ($payeParClasse[$classeId] ?? 0);
            $lignes[] = [
                'classe_id' => (int) $classeId,
                'classe' => (string) ($noms[$classeId] ?? 'Sans classe'),
                'du' => round($du, 2),
                'paye' => round($paye, 2),
                'taux' => round(min(100, max(0, $paye / $du * 100)), 1),
            ];
        }
        usort($lignes, fn ($a, $b) => $a['taux'] <=> $b['taux']);

        return array_slice($lignes, 0, self::CLASSES_AFFICHEES);
    }

    /**
     * La même série mensuelle pour l'année précédente, alignée mois par mois
     * sur l'année affichée (1er mois face au 1er mois).
     *
     * @return array{0: string|null, 1: array<int, float>}
     */
    private function serieAnneePrecedente(ComptabiliteFilters $filters, ?ESBTPAnneeUniversitaire $annee, int $nbMois): array
    {
        if (!$annee || !$annee->start_date || $nbMois === 0 || $filters->anneeId !== (int) $annee->id) {
            return [null, []];
        }

        $precedente = ESBTPAnneeUniversitaire::query()
            ->whereNotNull('start_date')
            ->where('start_date', '<', $annee->start_date)
            ->orderByDesc('start_date')
            ->first();
        if (!$precedente) {
            return [null, []];
        }

        $filtresPrecedents = new ComptabiliteFilters((int) $precedente->id, $filters->filiereId, $filters->classeId);
        [, $data] = $this->buildMonthlySeries($filtresPrecedents, $precedente);

        return [
            (string) ($precedente->name ?? $precedente->libelle ?? ''),
            array_slice(array_pad($data, $nbMois, 0.0), 0, $nbMois),
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
            ->selectRaw('YEAR(date_paiement) as y, MONTH(date_paiement) as m, SUM('.ESBTPPaiement::sqlCashCase().') as total')
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
            ->when($filters->filiereId, fn ($q) => $q->whereHas('inscription.classe', fn ($q2) => $q2->where('filiere_id', $filters->filiereId)))
            ->when($filters->classeId, fn ($q) => $q->whereHas('inscription', fn ($q2) => $q2->where('classe_id', $filters->classeId)))
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
