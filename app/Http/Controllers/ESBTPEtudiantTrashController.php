<?php

namespace App\Http\Controllers;

use App\Models\ESBTPEtudiant;
use App\Services\Trash\TrashAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ESBTPEtudiantTrashController extends Controller
{
    public function __construct(protected TrashAuditService $trashAudit)
    {
        $this->middleware('auth');
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
     */
    public function restore(int $id)
    {
        abort_unless(Auth::user()?->can('students.restore'), 403, 'Permission students.restore requise.');

        $etudiant = ESBTPEtudiant::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($etudiant) {
            $etudiant->restore();
            Log::info('Étudiant restauré depuis la corbeille', [
                'etudiant_id' => $etudiant->id,
                'restored_by' => Auth::id(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "L'étudiant {$etudiant->nom} {$etudiant->prenoms} a été restauré.",
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
}
