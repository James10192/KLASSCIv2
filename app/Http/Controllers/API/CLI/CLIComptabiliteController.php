<?php

namespace App\Http\Controllers\API\CLI;

use App\Helpers\SettingsHelper;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPPaiement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CLI endpoints dédiés au module comptable.
 *
 * Tous read-only, throttled par le groupe parent dans routes/api.php.
 * Utilisés pour :
 * - Audit comptable E2E (skill klassci-audit-comptable)
 * - Réconciliation paiements ↔ caisse physique (rule reconciliation-paiements-caisse)
 * - Diagnostic divergences UI ↔ DB pour résolution rapide
 */
class CLIComptabiliteController extends BaseApiController
{

    /**
     * GET /api/cli/comptabilite/dashboard-kpis
     *
     * Mirror EXACT des KPIs du Dashboard Compta UI, calculés sans filtre année par défaut
     * (= comportement actuel du dashboard). Permet de vérifier la cohérence entre ce que
     * voit l'utilisateur et la DB sans devoir scrapper l'HTML.
     *
     * Filtres optionnels : annee_id, filiere_id, classe_id.
     */
    public function dashboardKpis(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $anneeId = $request->input('annee_id');
        $filiereId = $request->input('filiere_id');
        $classeId = $request->input('classe_id');

        $query = ESBTPPaiement::query()->whereNull('deleted_at');
        if ($anneeId) {
            $query->whereHas('inscription', fn ($q) => $q->where('annee_universitaire_id', $anneeId));
        }
        if ($filiereId) {
            $query->whereHas('inscription.classe', fn ($q) => $q->where('filiere_id', $filiereId));
        }
        if ($classeId) {
            $query->whereHas('inscription', fn ($q) => $q->where('classe_id', $classeId));
        }

        $aggs = (clone $query)
            ->selectRaw(
                'COUNT(*) as total_count,
                 SUM(CASE WHEN status = ? THEN montant ELSE 0 END) as total_paid,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as count_validated,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as count_pending,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as count_rejected,
                 SUM(CASE WHEN status = ? THEN montant ELSE 0 END) as pending_amount',
                ['validé', 'validé', 'en_attente', 'rejeté', 'en_attente']
            )
            ->first();

        return $this->successResponse([
            'filters' => [
                'annee_id' => $anneeId,
                'filiere_id' => $filiereId,
                'classe_id' => $classeId,
            ],
            'paiements' => [
                'total_count' => (int) $aggs->total_count,
                'count_validated' => (int) $aggs->count_validated,
                'count_pending' => (int) $aggs->count_pending,
                'count_rejected' => (int) $aggs->count_rejected,
                'total_paid' => (float) $aggs->total_paid,
                'total_pending_amount' => (float) $aggs->pending_amount,
            ],
        ], 'Dashboard KPIs (mirror dashboard logic)');
    }

    /**
     * GET /api/cli/comptabilite/cash-balance
     *
     * Solde caisse par mode_paiement sur une période (par défaut aujourd'hui).
     * Indispensable pour la feature réconciliation : permet à un comptable de
     * connaître exactement le montant attendu en caisse par mode avant
     * de saisir son comptage physique.
     *
     * Params : date_debut, date_fin (default = today/today), status (default = validé).
     */
    public function cashBalance(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $dateDebut = $request->input('date_debut', now()->toDateString());
        $dateFin = $request->input('date_fin', now()->toDateString());
        $status = $request->input('status', 'validé');

        $rows = ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->whereDate('date_paiement', '>=', $dateDebut)
            ->whereDate('date_paiement', '<=', $dateFin)
            ->where('status', $status)
            ->selectRaw('mode_paiement, COUNT(*) as nb, COALESCE(SUM(montant), 0) as total')
            ->groupBy('mode_paiement')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'mode' => $r->mode_paiement ?? 'inconnu',
                'nb' => (int) $r->nb,
                'total' => (float) $r->total,
            ])
            ->all();

        $grand = array_sum(array_column($rows, 'total'));

        return $this->successResponse([
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'status' => $status,
            'by_mode' => $rows,
            'grand_total' => (float) $grand,
            'nb_paiements_total' => (int) array_sum(array_column($rows, 'nb')),
        ], 'Cash balance by mode');
    }

    /**
     * GET /api/cli/comptabilite/payments-summary
     *
     * Résumé multi-années : count + amount par année universitaire et par status.
     * Sert à identifier les paiements rattachés à des années passées qui
     * polluent les KPIs dashboard sans filtre année.
     */
    public function paymentsSummary(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $rows = ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->selectRaw(
                'annee_universitaire_id,
                 status,
                 COUNT(*) as nb,
                 COALESCE(SUM(montant), 0) as total'
            )
            ->groupBy('annee_universitaire_id', 'status')
            ->orderBy('annee_universitaire_id', 'desc')
            ->orderBy('status')
            ->get();

        $anneeIds = $rows->pluck('annee_universitaire_id')->filter()->unique()->all();
        $annees = ESBTPAnneeUniversitaire::whereIn('id', $anneeIds)
            ->get(['id', 'name', 'is_current'])
            ->keyBy('id');

        $byAnnee = [];
        foreach ($rows as $r) {
            $aid = $r->annee_universitaire_id ?? 0;
            $annee = $annees->get($aid);
            $key = $annee?->name ?? 'sans_annee';
            if (! isset($byAnnee[$key])) {
                $byAnnee[$key] = [
                    'annee_id' => $aid,
                    'annee_name' => $key,
                    'is_current' => $annee?->is_current ?? false,
                    'by_status' => [],
                    'total_count' => 0,
                    'total_amount' => 0,
                ];
            }
            $byAnnee[$key]['by_status'][$r->status] = [
                'nb' => (int) $r->nb,
                'amount' => (float) $r->total,
            ];
            $byAnnee[$key]['total_count'] += (int) $r->nb;
            $byAnnee[$key]['total_amount'] += (float) $r->total;
        }

        return $this->successResponse([
            'by_annee' => array_values($byAnnee),
            'grand_total_count' => array_sum(array_column($byAnnee, 'total_count')),
            'grand_total_amount' => array_sum(array_column($byAnnee, 'total_amount')),
        ], 'Payments summary by year × status');
    }

    /**
     * GET /api/cli/comptabilite/period-locks
     *
     * État du verrouillage de période comptable (setting `comptabilite.period_locked_until`).
     * Crucial pour comprendre quels paiements sont modifiables vs verrouillés.
     */
    public function periodLocks(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $lockedUntil = SettingsHelper::get('comptabilite.period_locked_until');
        $hasLock = !empty($lockedUntil);

        $modifiable = null;
        $locked = null;
        if ($hasLock) {
            try {
                $lockDate = \Carbon\Carbon::parse($lockedUntil)->endOfDay();
                $locked = ESBTPPaiement::query()
                    ->whereNull('deleted_at')
                    ->whereDate('date_paiement', '<=', $lockDate)
                    ->count();
                $modifiable = ESBTPPaiement::query()
                    ->whereNull('deleted_at')
                    ->whereDate('date_paiement', '>', $lockDate)
                    ->count();
            } catch (\Throwable $e) {
                $hasLock = false;
            }
        }

        return $this->successResponse([
            'period_locked_until' => $lockedUntil,
            'has_lock' => $hasLock,
            'paiements_verrouilles_count' => $locked,
            'paiements_modifiables_count' => $modifiable,
            'bypass_permission' => 'comptabilite.period.bypass_lock',
        ], 'Period locks state');
    }

    /**
     * GET /api/cli/comptabilite/orphan-paiements-annee-drift
     *
     * Identifie les paiements dont `paiement.annee_universitaire_id`
     * ne correspond PAS à `paiement.inscription.annee_universitaire_id`.
     * Cause typique : réinscription qui modifie l'inscription rattachée
     * sans synchroniser l'année du paiement.
     *
     * Découvert audit 2026-06-04 : stats(1429) vs dashboard-kpis(1428) = drift 1 paiement.
     */
    public function orphanPaiementsAnneeDrift(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        // Join directe inscription pour vraie corrélation cross-table
        $baseQuery = DB::table('esbtp_paiements as p')
            ->leftJoin('esbtp_inscriptions as i', 'p.inscription_id', '=', 'i.id')
            ->whereNull('p.deleted_at');

        // 3 catégories distinctes de "drift" :
        // 1. paiement avec annee != inscription.annee (les 2 valeurs non nulles)
        $driftedBoth = (clone $baseQuery)
            ->whereNotNull('p.annee_universitaire_id')
            ->whereNotNull('i.annee_universitaire_id')
            ->whereColumn('p.annee_universitaire_id', '!=', 'i.annee_universitaire_id')
            ->select('p.id', 'p.annee_universitaire_id as paiement_annee', 'i.annee_universitaire_id as inscription_annee', 'p.inscription_id', 'p.status', 'p.montant', 'p.date_paiement', 'p.motif', 'i.deleted_at as inscription_deleted_at')
            ->limit(50)
            ->get();

        // 2. paiement sans annee mais inscription en a une
        $noPaiementAnnee = (clone $baseQuery)
            ->whereNull('p.annee_universitaire_id')
            ->whereNotNull('i.annee_universitaire_id')
            ->count();

        // 3. paiement a annee mais inscription pas / inscription supprimée
        $noInscriptionAnnee = (clone $baseQuery)
            ->whereNotNull('p.annee_universitaire_id')
            ->where(function ($q) {
                $q->whereNull('i.annee_universitaire_id')
                  ->orWhereNotNull('i.deleted_at');
            })
            ->select('p.id', 'p.annee_universitaire_id as paiement_annee', 'p.inscription_id', 'p.status', 'p.montant', 'p.date_paiement', 'p.motif', 'i.deleted_at as inscription_deleted_at')
            ->limit(50)
            ->get();

        return $this->successResponse([
            'drifted_both_count' => $driftedBoth->count(),
            'drifted_both_sample' => $driftedBoth->all(),
            'no_paiement_annee_count' => $noPaiementAnnee,
            'paiement_annee_inscription_orphan_count' => $noInscriptionAnnee->count(),
            'paiement_annee_inscription_orphan_sample' => $noInscriptionAnnee->all(),
        ], 'Paiements avec drift année (paiement vs inscription) — 3 catégories');
    }

    /**
     * POST /api/cli/comptabilite/cleanup-orphan-paiements
     *
     * Soft-delete les paiements actifs dont l'inscription parente est elle-même soft-deletée.
     * One-shot cleanup pour aligner les KPIs après que le boot cascade ait été ajouté
     * (les anciens cas historiques d'avant le boot ne sont pas couverts par lui).
     *
     * Idempotent : run multiple fois est safe (chaque run ne touche que les nouveaux orphelins).
     *
     * Requires cli:admin ability.
     */
    public function cleanupOrphanPaiements(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $now = now();
        $affected = DB::table('esbtp_paiements as p')
            ->join('esbtp_inscriptions as i', 'p.inscription_id', '=', 'i.id')
            ->whereNull('p.deleted_at')
            ->whereNotNull('i.deleted_at')
            ->update([
                'p.deleted_at' => $now,
                'p.updated_at' => $now,
            ]);

        \Illuminate\Support\Facades\Log::warning('CLI: cleanup-orphan-paiements executed', [
            'user_id' => $request->user()->id,
            'affected_rows' => $affected,
            'executed_at' => $now->toDateTimeString(),
        ]);

        return $this->successResponse([
            'affected_rows' => $affected,
            'executed_at' => $now->toDateTimeString(),
            'note' => 'Paiements soft-deletés. Vérifier audit log + alignement KPIs stats vs dashboard-kpis.',
        ], "Cleanup orphan paiements ({$affected} affected)");
    }

    /**
     * GET /api/cli/comptabilite/reconciliation-candidates
     *
     * Identifie les paiements candidats à un audit / réconciliation :
     * - Paiements montant=0 (sentinelle ou erreur)
     * - Paiements sans inscription rattachée
     * - Paiements sans annee_universitaire_id
     * - Paiements en_attente depuis > N jours (param)
     */
    public function reconciliationCandidates(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $pendingDaysThreshold = (int) $request->input('pending_days', 7);

        $zeroAmount = ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->where('montant', 0)
            ->select(['id', 'etudiant_id', 'inscription_id', 'status', 'mode_paiement', 'motif', 'date_paiement', 'created_at'])
            ->limit(50)
            ->get();

        $noInscription = ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->whereNull('inscription_id')
            ->select(['id', 'etudiant_id', 'status', 'montant', 'date_paiement'])
            ->limit(50)
            ->get();

        $noAnnee = ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->whereNull('annee_universitaire_id')
            ->select(['id', 'etudiant_id', 'inscription_id', 'status', 'montant', 'date_paiement'])
            ->limit(50)
            ->get();

        $pendingOld = ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->where('status', 'en_attente')
            ->whereDate('created_at', '<=', now()->subDays($pendingDaysThreshold))
            ->select(['id', 'etudiant_id', 'inscription_id', 'montant', 'mode_paiement', 'date_paiement', 'created_at'])
            ->limit(50)
            ->get();

        return $this->successResponse([
            'pending_days_threshold' => $pendingDaysThreshold,
            'zero_amount_count' => $zeroAmount->count(),
            'zero_amount_sample' => $zeroAmount->all(),
            'no_inscription_count' => $noInscription->count(),
            'no_inscription_sample' => $noInscription->all(),
            'no_annee_count' => $noAnnee->count(),
            'no_annee_sample' => $noAnnee->all(),
            'pending_too_old_count' => $pendingOld->count(),
            'pending_too_old_sample' => $pendingOld->all(),
        ], 'Reconciliation candidates');
    }
}
