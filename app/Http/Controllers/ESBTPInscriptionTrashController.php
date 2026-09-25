<?php

namespace App\Http\Controllers;

use App\Support\ListeInfinie;
use App\Domain\Trash\Actions\ForceDeleteInscriptionWithDependencies;
use App\Domain\Trash\ErreurDeSuppression;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Services\Trash\TrashAuditService;
use App\Services\Trash\TrashDependencyAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ESBTPInscriptionTrashController extends Controller
{
    public function __construct(
        protected TrashAuditService $trashAudit,
        protected TrashDependencyAnalyzer $depAnalyzer,
    ) {
        $this->middleware('auth');
    }

    /**
     * GET /esbtp/trash/inscriptions/{id}/dependencies
     */
    public function dependencies(int $id)
    {
        abort_unless(Auth::user()?->can('trash.view'), 403, 'Accès à la corbeille refusé.');

        try {
            return response()->json([
                'success' => true,
                'data' => $this->depAnalyzer->forInscription($id),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription introuvable ou déjà supprimée définitivement.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur analyse dépendances inscription', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse des dépendances : '.$e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        abort_unless(Auth::user()?->can('trash.view'), 403, 'Accès à la corbeille refusé.');

        // Taille d'une tranche du defilement, bornee : la valeur vient de l'URL.
        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
        $search = trim((string) $request->input('search', ''));
        $range = $request->input('range');

        $query = ESBTPInscription::onlyTrashed()
            ->with(['etudiant:id,nom,prenoms,matricule', 'classe:id,name', 'anneeUniversitaire:id,name']);

        if ($search !== '') {
            $query->whereHas('etudiant', function ($q) use ($search) {
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

        // Departage par id : une suppression en masse tombe dans la meme seconde,
        // et la liste se charge par tranches.
        $inscriptions = $query->orderByDesc('deleted_at')->orderByDesc('id')->paginate($perPage);
        $deleters = $this->trashAudit->batchDeleters(ESBTPInscription::class, $inscriptions->getCollection());
        $kpis = $this->trashAudit->bucketsByAge(ESBTPInscription::class);

        return response()->json([
            'success' => true,
            'kpis' => $kpis,
            // Contrat du defilement (has_more, next_page, affiches), plus les
            // champs d'avant pour qui les lisait deja.
            'pagination' => ListeInfinie::pagination($inscriptions) + [
                'last_page' => $inscriptions->lastPage(),
                'per_page' => $inscriptions->perPage(),
            ],
            'items' => $inscriptions->getCollection()->map(function ($i) use ($deleters) {
                $etudiantSoftDeleted = $i->etudiant && $i->etudiant->trashed();
                // Sécurité : si etudiant chargé via withTrashed (relation might exclude trashed by default)
                $etudiantSoftDeleted = $i->etudiant_id
                    ? ESBTPEtudiant::onlyTrashed()->whereKey($i->etudiant_id)->exists()
                    : false;

                return [
                    'id' => $i->id,
                    'etudiant' => $i->etudiant ? [
                        'id' => $i->etudiant->id,
                        'nom' => $i->etudiant->nom,
                        'prenoms' => $i->etudiant->prenoms,
                        'matricule' => $i->etudiant->matricule,
                    ] : null,
                    'etudiant_soft_deleted' => $etudiantSoftDeleted,
                    'classe' => $i->classe?->name,
                    'annee' => $i->anneeUniversitaire?->name,
                    'status' => $i->status,
                    'deleted_at' => $i->deleted_at?->toIso8601String(),
                    'deleter' => $deleters[$i->id] ?? null,
                ];
            })->all(),
        ]);
    }

    public function restore(int $id)
    {
        abort_unless(Auth::user()?->can('inscriptions.restore'), 403, 'Permission inscriptions.restore requise.');

        $inscription = ESBTPInscription::onlyTrashed()->findOrFail($id);
        $etudiant = ESBTPEtudiant::withTrashed()->find($inscription->etudiant_id);
        $etudiantWasRestored = false;

        DB::transaction(function () use ($inscription, $etudiant, &$etudiantWasRestored) {
            // Cascade : si l'étudiant est lui-même soft-deleted, on le restaure aussi
            // (sinon l'inscription pointe sur un etudiant_id orphelin)
            if ($etudiant && $etudiant->trashed()) {
                $etudiant->restore();
                $etudiantWasRestored = true;
            }
            $inscription->restore();
        });

        Log::info('Inscription restaurée', [
            'inscription_id' => $inscription->id,
            'etudiant_cascade_restored' => $etudiantWasRestored,
            'restored_by' => Auth::id(),
        ]);

        $msg = "L'inscription a été restaurée.";
        if ($etudiantWasRestored) {
            $msg .= " L'étudiant {$etudiant->nom} {$etudiant->prenoms} a aussi été restauré (cascade).";
        }

        return response()->json(['success' => true, 'message' => $msg, 'etudiant_restored' => $etudiantWasRestored]);
    }

    public function forceDelete(int $id, ForceDeleteInscriptionWithDependencies $action)
    {
        abort_unless(Auth::user()?->can('inscriptions.force_delete'), 403, 'Permission inscriptions.force_delete requise.');

        try {
            $recap = $action->execute($id, Auth::user());

            $message = 'Inscription supprimée définitivement.';
            if ($recap['factures_deleted'] > 0) {
                $message .= " {$recap['factures_deleted']} facture(s) liée(s) ont été supprimées avec elle.";
            }

            return response()->json(['success' => true, 'message' => $message, 'result' => $recap]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription introuvable ou déjà supprimée définitivement.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression définitive inscription', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => ErreurDeSuppression::messageLisible($e, 'Cette inscription'),
            ], 422);
        }
    }
}
