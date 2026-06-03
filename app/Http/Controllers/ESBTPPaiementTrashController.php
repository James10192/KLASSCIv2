<?php

namespace App\Http\Controllers;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Services\Trash\TrashAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ESBTPPaiementTrashController extends Controller
{
    public function __construct(protected TrashAuditService $trashAudit)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        abort_unless(Auth::user()?->can('trash.view'), 403, 'Accès à la corbeille refusé.');

        $perPage = (int) $request->input('per_page', 20);
        $search = trim((string) $request->input('search', ''));
        $range = $request->input('range');

        $query = ESBTPPaiement::onlyTrashed()
            ->with(['inscription:id,etudiant_id,classe_id', 'inscription.etudiant:id,nom,prenoms,matricule']);

        if ($search !== '') {
            $query->whereHas('inscription.etudiant', function ($q) use ($search) {
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

        $paiements = $query->orderByDesc('deleted_at')->paginate($perPage);
        $deleters = $this->trashAudit->batchDeleters(ESBTPPaiement::class, $paiements->getCollection());
        $kpis = $this->trashAudit->bucketsByAge(ESBTPPaiement::class);

        return response()->json([
            'success' => true,
            'kpis' => $kpis,
            'items' => $paiements->getCollection()->map(function ($p) use ($deleters) {
                $etudiant = $p->inscription?->etudiant;
                $etudiantSoftDeleted = $etudiant?->id
                    ? ESBTPEtudiant::onlyTrashed()->whereKey($etudiant->id)->exists()
                    : false;
                $inscriptionSoftDeleted = $p->inscription_id
                    ? ESBTPInscription::onlyTrashed()->whereKey($p->inscription_id)->exists()
                    : false;

                return [
                    'id' => $p->id,
                    'reference' => $p->reference ?? null,
                    'montant' => (float) ($p->montant ?? 0),
                    'mode_paiement' => $p->mode_paiement ?? null,
                    'etudiant' => $etudiant ? [
                        'id' => $etudiant->id,
                        'nom' => $etudiant->nom,
                        'prenoms' => $etudiant->prenoms,
                        'matricule' => $etudiant->matricule,
                    ] : null,
                    'etudiant_soft_deleted' => $etudiantSoftDeleted,
                    'inscription_soft_deleted' => $inscriptionSoftDeleted,
                    'date_paiement' => $p->date_paiement ? \Carbon\Carbon::parse($p->date_paiement)->format('d/m/Y') : null,
                    'deleted_at' => $p->deleted_at?->toIso8601String(),
                    'deleter' => $deleters[$p->id] ?? null,
                ];
            })->all(),
            'pagination' => [
                'current_page' => $paiements->currentPage(),
                'last_page' => $paiements->lastPage(),
                'per_page' => $paiements->perPage(),
                'total' => $paiements->total(),
            ],
        ]);
    }

    public function restore(int $id)
    {
        abort_unless(Auth::user()?->can('paiements.restore'), 403, 'Permission paiements.restore requise.');

        $paiement = ESBTPPaiement::onlyTrashed()->findOrFail($id);
        $inscription = ESBTPInscription::withTrashed()->find($paiement->inscription_id);
        $etudiant = $inscription ? ESBTPEtudiant::withTrashed()->find($inscription->etudiant_id) : null;
        $cascadeRestored = ['inscription' => false, 'etudiant' => false];

        DB::transaction(function () use ($paiement, $inscription, $etudiant, &$cascadeRestored) {
            if ($etudiant && $etudiant->trashed()) {
                $etudiant->restore();
                $cascadeRestored['etudiant'] = true;
            }
            if ($inscription && $inscription->trashed()) {
                $inscription->restore();
                $cascadeRestored['inscription'] = true;
            }
            $paiement->restore();
        });

        Log::info('Paiement restauré', [
            'paiement_id' => $paiement->id,
            'cascade' => $cascadeRestored,
            'restored_by' => Auth::id(),
        ]);

        $messages = ["Le paiement a été restauré."];
        if ($cascadeRestored['inscription']) $messages[] = "L'inscription associée a aussi été restaurée (cascade).";
        if ($cascadeRestored['etudiant']) $messages[] = "L'étudiant ".($etudiant->nom ?? '')." a aussi été restauré (cascade).";

        return response()->json([
            'success' => true,
            'message' => implode(' ', $messages),
            'cascade' => $cascadeRestored,
        ]);
    }

    public function forceDelete(int $id)
    {
        abort_unless(Auth::user()?->can('paiements.force_delete'), 403, 'Permission paiements.force_delete requise.');

        $paiement = ESBTPPaiement::onlyTrashed()->findOrFail($id);

        try {
            DB::transaction(fn () => $paiement->forceDelete());
            Log::warning('Paiement supprimé définitivement', ['paiement_id' => $id, 'deleted_by' => Auth::id()]);
            return response()->json(['success' => true, 'message' => "Paiement supprimé définitivement."]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Suppression impossible : '.$e->getMessage()], 422);
        }
    }
}
