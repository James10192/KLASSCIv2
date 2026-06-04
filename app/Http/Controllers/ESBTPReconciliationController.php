<?php

namespace App\Http\Controllers;

use App\Domain\Comptabilite\Reconciliation\Actions\ApproveSession;
use App\Domain\Comptabilite\Reconciliation\Actions\CloseSession;
use App\Domain\Comptabilite\Reconciliation\Actions\OpenSession;
use App\Domain\Comptabilite\Reconciliation\Actions\RecordCashCount;
use App\Domain\Comptabilite\Reconciliation\Actions\ReopenSession;
use App\Domain\Comptabilite\Reconciliation\Actions\ResolveDiscrepancy;
use App\Domain\Comptabilite\Reconciliation\Actions\ReviewSession;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationDiscrepancy;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Http\Requests\Reconciliation\OpenSessionRequest;
use App\Http\Requests\Reconciliation\RecordCashCountRequest;
use App\Http\Requests\Reconciliation\ReopenSessionRequest;
use App\Http\Requests\Reconciliation\ResolveDiscrepancyRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Orchestration uniquement (rule no-god-code-compta).
 * Toute logique métier vit dans Domain/Comptabilite/Reconciliation/.
 *
 * Noms de méthodes route NON-réservés Laravel (rule controller-naming) :
 * - index / show / open / recordCount / resolve
 * - review / approve / close / reopen
 */
class ESBTPReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('comptabilite.reconciliation.view');

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

        $sessions = $query->paginate(20);
        $kpis = $this->buildKpis();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'sessions' => $sessions,
                'kpis' => $kpis,
            ]);
        }
        return view('esbtp.comptabilite.reconciliation.index', compact('sessions', 'kpis'));
    }

    public function create(Request $request)
    {
        $this->authorize('comptabilite.reconciliation.open');
        $defaultFrequency = \App\Helpers\SettingsHelper::get('comptabilite.reconciliation.frequency', 'daily');
        return view('esbtp.comptabilite.reconciliation.create', compact('defaultFrequency'));
    }

    public function show(Request $request, ReconciliationSession $session)
    {
        $this->authorize('comptabilite.reconciliation.view');

        $session->load([
            'opener:id,name',
            'reviewer:id,name',
            'approver:id,name',
            'closer:id,name',
            'cashCounts',
            'discrepancies',
        ]);

        $payload = [
            'session' => $session,
            'cash_counts' => $session->cashCounts,
            'discrepancies' => $session->discrepancies,
            'total_ecart' => $session->totalEcart(),
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($payload);
        }
        return view('esbtp.comptabilite.reconciliation.show', $payload);
    }

    private function buildKpis(): array
    {
        return [
            'draft' => ReconciliationSession::where('status', 'draft')->count(),
            'review' => ReconciliationSession::where('status', 'review')->count(),
            'approved' => ReconciliationSession::where('status', 'approved')->count(),
            'closed' => ReconciliationSession::where('status', 'closed')->count(),
            'total' => ReconciliationSession::count(),
        ];
    }

    public function open(OpenSessionRequest $request, OpenSession $action): JsonResponse
    {
        $session = $action->execute(
            $request->user(),
            $request->input('frequency'),
            $request->input('start_date'),
        );
        return response()->json(['session' => $session, 'message' => 'Session ouverte.'], 201);
    }

    public function recordCount(
        RecordCashCountRequest $request,
        ReconciliationSession $session,
        RecordCashCount $action
    ): JsonResponse {
        $count = $action->execute(
            $session,
            $request->user(),
            $request->input('mode_paiement'),
            (float) $request->input('montant_compte'),
            $request->input('notes'),
        );
        return response()->json(['cash_count' => $count, 'ecart' => $count->ecart]);
    }

    public function resolve(
        ResolveDiscrepancyRequest $request,
        ReconciliationDiscrepancy $discrepancy,
        ResolveDiscrepancy $action
    ): JsonResponse {
        $resolved = $action->execute(
            $discrepancy,
            $request->user(),
            $request->input('resolution_type'),
            $request->input('motif'),
            $request->input('payload', []),
        );
        return response()->json(['discrepancy' => $resolved, 'message' => 'Écart résolu.']);
    }

    public function review(
        Request $request,
        ReconciliationSession $session,
        ReviewSession $action
    ): JsonResponse {
        $this->authorize('comptabilite.reconciliation.open');
        $action->execute($session, $request->user());
        return response()->json(['session' => $session->refresh(), 'message' => 'Session en revue.']);
    }

    public function approve(
        Request $request,
        ReconciliationSession $session,
        ApproveSession $action
    ): JsonResponse {
        $this->authorize('comptabilite.reconciliation.approve');
        try {
            $action->execute($session, $request->user());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['session' => $session->refresh(), 'message' => 'Session approuvée.']);
    }

    public function close(
        Request $request,
        ReconciliationSession $session,
        CloseSession $action
    ): JsonResponse {
        $this->authorize('comptabilite.reconciliation.approve');
        $action->execute($session, $request->user());
        return response()->json(['session' => $session->refresh(), 'message' => 'Session clôturée.']);
    }

    public function reopen(
        ReopenSessionRequest $request,
        ReconciliationSession $session,
        ReopenSession $action
    ): JsonResponse {
        $action->execute($session, $request->user(), $request->input('reason'));
        return response()->json(['session' => $session->refresh(), 'message' => 'Session rouverte (exception).']);
    }

    public function exportPv(
        Request $request,
        ReconciliationSession $session,
        \App\Services\ExportRenderer $renderer
    ) {
        $this->authorize('comptabilite.reconciliation.export');
        $report = new \App\Domain\Exports\Reports\ReconciliationPVReport($session);
        return $renderer->pdfDownload($report);
    }
}
