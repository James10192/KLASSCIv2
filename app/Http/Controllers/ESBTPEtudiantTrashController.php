<?php

namespace App\Http\Controllers;

use App\Domain\Trash\Actions\ForceDeleteEtudiantWithDependencies;
use App\Models\ESBTPEtudiant;
use App\Services\Trash\TrashAuditService;
use App\Services\Trash\TrashDependencyAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ESBTPEtudiantTrashController extends Controller
{
    public function __construct(
        protected TrashAuditService $trashAudit,
        protected TrashDependencyAnalyzer $depAnalyzer,
    ) {
        $this->middleware('auth');
    }

    /**
     * GET /esbtp/trash/etudiants/{id}/dependencies
     * Analyse les dépendances bloquantes / cascadantes pour les actions
     * Restaurer + Supprimer définitivement. Affiché dans un dialog UI premium.
     */
    public function dependencies(int $id)
    {
        abort_unless(Auth::user()?->can('trash.view'), 403, 'Accès à la corbeille refusé.');

        try {
            return response()->json([
                'success' => true,
                'data' => $this->depAnalyzer->forEtudiant($id),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable ou déjà supprimé définitivement.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur analyse dépendances étudiant', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse des dépendances : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /esbtp/trash/etudiants — JSON liste paginée + KPIs (consommé par vue tabs).
     */
    public function index(Request $request)
    {
        abort_unless(Auth::user()?->can('trash.view'), 403, 'Accès à la corbeille refusé.');

        $perPage = (int) $request->input('per_page', 20);
        $search = trim((string) $request->input('search', ''));
        $range = $request->input('range'); // null|'this_week'|'this_month'|'older'

        $query = ESBTPEtudiant::onlyTrashed();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenoms', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $now = now();
        if ($range === 'this_week') {
            $query->where('deleted_at', '>=', $now->copy()->subDays(7));
        } elseif ($range === 'this_month') {
            $query->where('deleted_at', '>=', $now->copy()->subDays(30));
        } elseif ($range === 'older') {
            $query->where('deleted_at', '<', $now->copy()->subDays(30));
        }

        $etudiants = $query->orderByDesc('deleted_at')->paginate($perPage);
        $deleters = $this->trashAudit->batchDeleters(ESBTPEtudiant::class, $etudiants->getCollection());
        $kpis = $this->trashAudit->bucketsByAge(ESBTPEtudiant::class);

        return response()->json([
            'success' => true,
            'kpis' => $kpis,
            'items' => $etudiants->getCollection()->map(fn ($e) => [
                'id' => $e->id,
                'matricule' => $e->matricule,
                'nom' => $e->nom,
                'prenoms' => $e->prenoms,
                'photo' => $e->photo,
                'classe_id' => $e->classe_id,
                'deleted_at' => $e->deleted_at?->toIso8601String(),
                'deleter' => $deleters[$e->id] ?? null,
            ])->all(),
            'pagination' => [
                'current_page' => $etudiants->currentPage(),
                'last_page' => $etudiants->lastPage(),
                'per_page' => $etudiants->perPage(),
                'total' => $etudiants->total(),
            ],
        ]);
    }

    /**
     * POST /esbtp/trash/etudiants/{id}/restore — Restore un étudiant soft-deleted.
     *
     * Le hook ESBTPEtudiant::booted::restoring cascade automatiquement les
     * inscriptions soft-deletées dans la fenêtre ±2 min autour du deleted_at,
     * qui à leur tour cascadent à leurs paiements via leur propre booted.
     */
    public function restore(int $id)
    {
        abort_unless(Auth::user()?->can('students.restore'), 403, 'Permission students.restore requise.');

        $etudiant = ESBTPEtudiant::onlyTrashed()->findOrFail($id);

        // Compteurs pour audit + message utilisateur
        $cutoff = $etudiant->deleted_at;
        $window = $cutoff
            ? [$cutoff->copy()->subMinutes(2), $cutoff->copy()->addMinutes(2)]
            : null;

        $inscriptionsCascadees = 0;
        $paiementsCascades = 0;

        if ($window) {
            $inscriptionsCascadees = DB::table('esbtp_inscriptions')
                ->where('etudiant_id', $id)
                ->whereNotNull('deleted_at')
                ->whereBetween('deleted_at', $window)
                ->count();

            $paiementsCascades = DB::table('esbtp_paiements')
                ->where('etudiant_id', $id)
                ->whereNotNull('deleted_at')
                ->whereBetween('deleted_at', $window)
                ->count();
        }

        DB::transaction(function () use ($etudiant) {
            $etudiant->restore();  // déclenche booted::restoring cascade
            Log::info('Étudiant restauré depuis la corbeille', [
                'etudiant_id' => $etudiant->id,
                'restored_by' => Auth::id(),
            ]);
        });

        $msg = "L'étudiant {$etudiant->nom} {$etudiant->prenoms} a été restauré.";
        if ($inscriptionsCascadees > 0) {
            $msg .= " {$inscriptionsCascadees} inscription(s) restaurée(s) en cascade.";
        }
        if ($paiementsCascades > 0) {
            $msg .= " {$paiementsCascades} paiement(s) restauré(s) en cascade.";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'cascade' => [
                'inscriptions' => $inscriptionsCascadees,
                'paiements' => $paiementsCascades,
            ],
        ]);
    }

    /**
     * DELETE /esbtp/trash/etudiants/{id}/force — Suppression définitive irréversible.
     */
    public function forceDelete(int $id)
    {
        abort_unless(Auth::user()?->can('students.force_delete'), 403, 'Permission students.force_delete requise.');

        $etudiant = ESBTPEtudiant::onlyTrashed()->findOrFail($id);
        $label = trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? ''));

        try {
            DB::transaction(function () use ($etudiant) {
                $etudiant->forceDelete();
            });

            Log::warning('Étudiant supprimé définitivement', [
                'etudiant_id' => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "L'étudiant {$label} a été supprimé définitivement.",
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la suppression définitive', [
                'etudiant_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Suppression définitive impossible — l\'étudiant a probablement des dépendances non-cascadables (inscriptions/paiements non-supprimés). Détail : '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /esbtp/trash/etudiants/{id}/force-delete-cascade — Suppression cascade
     *
     * Action exceptionnelle gardée par permission `students.force_delete_cascade`
     * (superAdmin par défaut). Supprime définitivement l'étudiant ET tous ses
     * enfants (inscriptions, paiements, notes, absences, frais souscriptions)
     * en cascade explicite.
     *
     * Bloque si des paiements VALIDÉS actifs existent (intégrité OHADA).
     * Exige un motif texte ≥ 30 caractères (preuve audit).
     *
     * Verbe métier non-réservé (rule controller-naming) : `forceDeleteCascade`.
     */
    public function forceDeleteCascade(Request $request, int $id, ForceDeleteEtudiantWithDependencies $action)
    {
        abort_unless(
            Auth::user()?->can('students.force_delete_cascade'),
            403,
            'Permission students.force_delete_cascade requise (action destructive cascade).'
        );

        $validated = $request->validate([
            'motif' => ['required', 'string', 'min:30', 'max:2000'],
        ], [
            'motif.required' => 'Motif obligatoire pour la suppression cascade.',
            'motif.min' => 'Motif doit faire au moins 30 caractères (justification audit).',
        ]);

        try {
            $result = $action->execute(
                etudiantId: $id,
                user: Auth::user(),
                motif: $validated['motif']
            );

            return response()->json([
                'success' => true,
                'message' => "Étudiant {$result['etudiant_label']} supprimé définitivement en cascade : "
                    ."{$result['inscriptions_deleted']} inscription(s), "
                    ."{$result['paiements_deleted']} paiement(s), "
                    ."{$result['notes_cascade']} note(s) et "
                    ."{$result['absences_cascade']} présence(s) supprimées.",
                'result' => $result,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable ou déjà supprimé définitivement.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la suppression cascade', [
                'etudiant_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la suppression cascade : '.$e->getMessage(),
            ], 500);
        }
    }
}
