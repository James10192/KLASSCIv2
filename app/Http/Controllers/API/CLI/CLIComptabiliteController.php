<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
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
            ->selectRaw('mode_paiement, COUNT(*) as nb, COALESCE(SUM('.ESBTPPaiement::sqlCashCase().'), 0) as total')
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
                 COALESCE(SUM('.ESBTPPaiement::sqlCashCase().'), 0) as total'
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
    /**
     * GET /api/cli/comptabilite/reconciliation/sessions
     *
     * Liste les sessions de réconciliation (filtre status optionnel).
     */
    public function reconciliationSessions(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $query = ReconciliationSession::query()
            ->with(['opener:id,name', 'approver:id,name'])
            ->withCount(['cashCounts', 'discrepancies'])
            ->orderByDesc('opened_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($frequency = $request->input('frequency')) {
            $query->where('frequency', $frequency);
        }

        $sessions = $query->limit(50)->get();

        return $this->successResponse([
            'count' => $sessions->count(),
            'sessions' => $sessions->map(fn (ReconciliationSession $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'frequency' => $s->frequency,
                'period_start' => $s->period_start?->toDateString(),
                'period_end' => $s->period_end?->toDateString(),
                'status' => $s->status->value,
                'opener' => $s->opener?->name,
                'approver' => $s->approver?->name,
                'cash_counts_count' => $s->cash_counts_count,
                'discrepancies_count' => $s->discrepancies_count,
                'total_ecart' => $s->totalEcart(),
                'opened_at' => $s->opened_at?->toIso8601String(),
                'closed_at' => $s->closed_at?->toIso8601String(),
            ])->all(),
        ], 'Reconciliation sessions');
    }

    /**
     * GET /api/cli/comptabilite/reconciliation/metrics
     *
     * Métriques détaillées (PR6) : santé + KPIs analytiques (avg_days_to_close,
     * pct_no_ecart, avg_ecart_by_mode sur 90j). Délègue au Service Domain.
     */
    public function reconciliationMetrics(Request $request, \App\Domain\Comptabilite\Reconciliation\Services\ReconciliationMetricsService $service): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }
        return $this->successResponse($service->snapshot(), 'Reconciliation metrics snapshot');
    }

    /**
     * GET /api/cli/comptabilite/reconciliation/health
     *
     * Dashboard santé réconciliation : combien de sessions en draft/review/overdue.
     * Overdue = session ouverte il y a > 7 jours encore en draft.
     */
    public function reconciliationHealth(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $now = now();
        $overdueThreshold = $now->copy()->subDays(7);

        $byStatus = ReconciliationSession::query()
            ->selectRaw('status, COUNT(*) as nb')
            ->groupBy('status')
            ->pluck('nb', 'status')
            ->all();

        $overdueDraft = ReconciliationSession::query()
            ->where('status', 'draft')
            ->where('opened_at', '<', $overdueThreshold)
            ->count();

        $lastClosedAt = ReconciliationSession::query()
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->value('closed_at');

        $daysSinceLastClose = $lastClosedAt ? $now->diffInDays($lastClosedAt) : null;

        return $this->successResponse([
            'by_status' => [
                'draft' => (int) ($byStatus['draft'] ?? 0),
                'review' => (int) ($byStatus['review'] ?? 0),
                'approved' => (int) ($byStatus['approved'] ?? 0),
                'closed' => (int) ($byStatus['closed'] ?? 0),
                'reopened' => (int) ($byStatus['reopened'] ?? 0),
            ],
            'overdue_draft_count' => $overdueDraft,
            'last_close_at' => $lastClosedAt?->toIso8601String(),
            'days_since_last_close' => $daysSinceLastClose,
            'health_status' => $overdueDraft > 0 ? 'degraded' : ($daysSinceLastClose && $daysSinceLastClose > 7 ? 'warning' : 'ok'),
        ], 'Reconciliation health');
    }

    /**
     * GET /api/cli/comptabilite/reconciliation/sessions/{id}
     */
    public function reconciliationSessionShow(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $session = ReconciliationSession::with([
            'opener:id,name',
            'reviewer:id,name',
            'approver:id,name',
            'closer:id,name',
            'cashCounts',
            'discrepancies',
        ])->find($id);

        if (!$session) {
            return $this->errorResponse('Session not found', [], 404);
        }

        return $this->successResponse([
            'session' => [
                'id' => $session->id,
                'code' => $session->code,
                'frequency' => $session->frequency,
                'period_start' => $session->period_start?->toDateString(),
                'period_end' => $session->period_end?->toDateString(),
                'status' => $session->status->value,
                'status_label' => $session->status->label(),
                'opener' => $session->opener?->name,
                'reviewer' => $session->reviewer?->name,
                'approver' => $session->approver?->name,
                'closer' => $session->closer?->name,
                'opened_at' => $session->opened_at?->toIso8601String(),
                'reviewed_at' => $session->reviewed_at?->toIso8601String(),
                'approved_at' => $session->approved_at?->toIso8601String(),
                'closed_at' => $session->closed_at?->toIso8601String(),
                'reopen_reason' => $session->reopen_reason,
                'notes' => $session->notes,
                'total_ecart' => $session->totalEcart(),
            ],
            'cash_counts' => $session->cashCounts->map(fn ($c) => [
                'id' => $c->id,
                'mode' => $c->mode_paiement,
                'mode_label' => $c->modeLabel(),
                'montant_compte' => (float) $c->montant_compte,
                'montant_systeme' => (float) $c->montant_systeme,
                'ecart' => $c->ecart,
                'counted_at' => $c->counted_at?->toIso8601String(),
            ])->all(),
            'discrepancies' => $session->discrepancies->map(fn ($d) => [
                'id' => $d->id,
                'type' => $d->type,
                'montant_ecart' => (float) $d->montant_ecart,
                'action' => $d->action,
                'motif' => $d->motif,
            ])->all(),
        ], "Session {$session->code}");
    }

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

    /**
     * GET /api/cli/comptabilite/recus-en-double
     *
     * Deux recus portant le meme numero, c'est deux preuves de paiement
     * indiscernables : en cas de contestation, rien ne dit laquelle est la
     * bonne. `genererNumeroRecu()` verrouille desormais sa lecture du dernier
     * numero, mais ce verrou ne protege pas la toute premiere emission d'une
     * annee — il n'y a alors aucune ligne a verrouiller. Seul un index unique
     * ferme cette fenetre.
     *
     * Cet endpoint repond a la question qui conditionne cette migration :
     * la base contient-elle DEJA des doublons ? Si oui, l'index echoue a la
     * pose, et il faut d'abord trancher quel recu garde son numero.
     *
     * Trois pieges, verifies ici plutot que supposes :
     * - un index unique porte sur TOUTES les lignes, y compris celles que le
     *   soft-delete a retirees de la vue. Un numero rendu a un paiement
     *   supprime bloque la migration.
     * - MySQL tolere plusieurs NULL sur une colonne unique, mais PAS
     *   plusieurs chaines vides. Les deux cas se comptent separement.
     * - le meme numero peut coexister sur deux annees universitaires ; c'est
     *   un doublon quand meme, la colonne ne portant pas l'annee.
     *
     * Lecture seule. Aucune ecriture, aucune suggestion de suppression : le
     * choix du recu qui garde son numero appartient a la comptabilite.
     */
    public function recusEnDouble(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $groupes = DB::table('esbtp_paiements')
            ->select('numero_recu', DB::raw('COUNT(*) as occurrences'), DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
            ->whereNotNull('numero_recu')
            ->where('numero_recu', '!=', '')
            ->groupBy('numero_recu')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('occurrences')
            ->get();

        // Les memes, en ignorant les lignes supprimees : l'ecart entre les deux
        // dit combien de doublons ne viennent QUE du soft-delete.
        $groupesVisibles = DB::table('esbtp_paiements')
            ->select('numero_recu', DB::raw('COUNT(*) as occurrences'))
            ->whereNull('deleted_at')
            ->whereNotNull('numero_recu')
            ->where('numero_recu', '!=', '')
            ->groupBy('numero_recu')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $vides = DB::table('esbtp_paiements')->where('numero_recu', '')->count();
        $nuls = DB::table('esbtp_paiements')->whereNull('numero_recu')->count();
        $total = DB::table('esbtp_paiements')->count();

        $bloquants = $groupes->count() + ($vides > 1 ? 1 : 0);

        // Une garantie qu'on ne peut pas constater n'en est pas une : on lit
        // le schema plutot que de supposer que la migration est passee.
        $contraintePosee = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'esbtp_paiements')
            ->where('INDEX_NAME', 'esbtp_paiements_recu_en_circulation_unique')
            ->exists();

        return $this->successResponse([
            'contrainte_posee' => $contraintePosee,
            'paiements_total' => $total,
            'numeros_en_double' => $groupes->count(),
            'lignes_concernees' => (int) $groupes->sum('occurrences'),
            'dont_visibles_hors_supprimes' => $groupesVisibles->count(),
            'numero_vide' => $vides,
            'numero_nul' => $nuls,
            'index_unique_posable' => $bloquants === 0,
            'ce_qui_bloque' => $bloquants === 0
                ? null
                : trim(($groupes->count() > 0 ? $groupes->count() . ' numero(s) en double. ' : '')
                    . ($vides > 1 ? $vides . ' paiements portent une chaine vide, que MySQL refuse en double (le NULL, lui, est tolere).' : '')),
            'echantillon' => $groupes->take(25)->values()->all(),
        ], 'Diagnostic des numeros de recu');
    }

    /**
     * GET /api/cli/comptabilite/reliquats-comptes-en-double
     *
     * Un versement de reliquat porte `type_paiement = 'reliquat'` et
     * l'inscription de DESTINATION : il transite par l'annee en cours, mais il
     * eteint une dette de l'annee precedente. `netPaidForInscription()` le
     * comptait comme un paiement de la scolarite courante — un etudiant reglant
     * 250 000 d'arriere ressortait crediteur sur son annee, et
     * `peutSeReinscrire()` lui ouvrait l'annee suivante alors qu'il devait
     * encore la sienne.
     *
     * Le calcul est corrige. Cet endpoint repond a l'autre question, celle que
     * le correctif ne traite pas : le defaut avait-il DEJA mordu, et sur qui ?
     *
     * Il ne suffit pas de compter les reliquats. Un reliquat n'a fausse un
     * verdict que s'il a fait BASCULER l'inscription du cote « a jour » — le
     * cas ou l'etudiant restait debiteur meme en le comptant, ou etait deja
     * solde sans lui, n'a trompe personne. C'est cette bascule qui est
     * comptee ici, inscription par inscription.
     *
     * Lecture seule.
     */
    public function reliquatsComptesEnDouble(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $reliquats = DB::table('esbtp_paiements')
            ->select('inscription_id', DB::raw('SUM(montant) as total'), DB::raw('COUNT(*) as nb'))
            ->whereNull('deleted_at')
            ->where('type_paiement', 'reliquat')
            ->where('status', 'validé')
            ->whereNotNull('inscription_id')
            ->groupBy('inscription_id')
            ->get();

        if ($reliquats->isEmpty()) {
            return $this->successResponse([
                'versements_reliquat' => 0,
                'montant_total' => 0.0,
                'inscriptions_concernees' => 0,
                'verdicts_fausses' => 0,
                'detail' => [],
            ], 'Aucun versement de reliquat : le defaut n a jamais pu mordre ici');
        }

        $detail = [];
        $fausses = 0;

        foreach ($reliquats as $ligne) {
            $attendu = (float) DB::table('esbtp_frais_subscriptions')
                ->where('inscription_id', $ligne->inscription_id)
                ->where('is_active', true)
                ->sum('amount');

            $payeHorsReliquat = (float) DB::table('esbtp_paiements')
                ->whereNull('deleted_at')
                ->where('inscription_id', $ligne->inscription_id)
                ->where('status', 'validé')
                ->where(fn ($q) => $q->where('type_paiement', '!=', 'reliquat')->orWhereNull('type_paiement'))
                ->sum('montant');

            $payeAvecReliquat = $payeHorsReliquat + (float) $ligne->total;

            // La bascule : credite a tort hier, debiteur en verite.
            $verdictFausse = $attendu > 0
                && $payeAvecReliquat >= $attendu
                && $payeHorsReliquat < $attendu;

            if ($verdictFausse) {
                $fausses++;
            }

            $detail[] = [
                'inscription_id' => (int) $ligne->inscription_id,
                'versements_reliquat' => (int) $ligne->nb,
                'montant_reliquat' => (float) $ligne->total,
                'attendu' => $attendu,
                'paye_hors_reliquat' => $payeHorsReliquat,
                'reste_du_reel' => max(0.0, $attendu - $payeHorsReliquat),
                'verdict_fausse' => $verdictFausse,
            ];
        }

        usort($detail, fn ($a, $b) => ($b['verdict_fausse'] <=> $a['verdict_fausse'])
            ?: ($b['reste_du_reel'] <=> $a['reste_du_reel']));

        return $this->successResponse([
            'versements_reliquat' => (int) $reliquats->sum('nb'),
            'montant_total' => (float) $reliquats->sum('total'),
            'inscriptions_concernees' => $reliquats->count(),
            'verdicts_fausses' => $fausses,
            'detail' => array_slice($detail, 0, 40),
        ], 'Impact historique du reliquat compte comme paiement courant');
    }

    /**
     * POST /api/cli/comptabilite/recus-en-double/renumeroter
     *
     * Simule par defaut (`dry_run=1`). La logique vit dans l'action dediee ;
     * on ne fait ici que la declencher et rendre son compte-rendu.
     */
    public function renumeroterLesRecusEnDouble(
        Request $request,
        \App\Domain\Comptabilite\Receipts\Actions\RenumeroterLesRecusEnDouble $action
    ): JsonResponse {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $simulation = $request->boolean('dry_run', true);
        $resultat = $action->executer($simulation);

        if ($resultat['refus'] !== []) {
            return $this->errorResponse(
                'Renumerotation refusee : un numero au moins porte plusieurs recus vivants. Rien n a ete ecrit.',
                $resultat,
                422
            );
        }

        return $this->successResponse(
            $resultat,
            $simulation ? 'Simulation — rien n a ete ecrit' : 'Renumerotation effectuee'
        );
    }
}
