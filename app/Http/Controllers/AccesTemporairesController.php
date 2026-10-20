<?php

namespace App\Http\Controllers;

use App\Domain\Permissions\AccesTemporaireRefuse;
use App\Domain\Permissions\AccesTemporaires;
use App\Http\Requests\Permissions\StoreAccesTemporaireRequest;
use App\Models\TemporaryPermissionGrant;
use App\Models\User;
use App\Services\PermissionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * /esbtp/acces-individuels — permissions personnalisees temporaires ou definitives.
 *
 * Le controleur ne decide de rien : les refus (permission non accordable, duree
 * trop longue, auteur qui ne la detient pas, chevauchement) vivent dans
 * AccesTemporaires, pour que l'ecran et le CLI refusent la meme chose.
 */
class AccesTemporairesController extends Controller
{
    public function __construct(
        private readonly AccesTemporaires $acces,
        private readonly PermissionRegistry $registry,
    ) {
    }

    public function index(Request $request)
    {
        $personnel = User::query()
            ->select('id', 'name', 'email', 'username')
            ->with('roles:id,name')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'etudiant'))
            ->orderBy('name')
            ->get();

        // Depuis la fiche d'une personne (liste du personnel), l'écran s'ouvre
        // avec elle déjà choisie. Un identifiant hors de la liste est ignoré.
        $choisi = (int) $request->query('user_id');

        return view('esbtp.acces-temporaires.index', [
            'personnel' => $personnel,
            'personneChoisie' => $personnel->contains('id', $choisi) ? $choisi : null,
            'permissions' => $this->permissionsAccordables(),
            'dureeMaxJours' => $this->acces->dureeMaxJours(),
            'listeInitiale' => $this->liste('en_cours', 1),
            'statistiques' => $this->statistiques(),
            'peutAccorderDefinitif' => $this->acces->peutGererPermanents($request->user()),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        // Sans scope, conserver la reponse historique utilisee par les anciens clients.
        if (! $request->has('scope')) {
            return response()->json(['acces' => $this->listeHistoriqueRecente(), 'statistiques' => $this->statistiques()]);
        }

        $scope = in_array($request->query('scope'), ['en_cours', 'definitifs', 'tous'], true)
            ? $request->query('scope') : 'en_cours';

        return response()->json([
            ...$this->liste($scope, max(1, (int) $request->query('page', 1))),
            'statistiques' => $this->statistiques(),
        ]);
    }

    public function store(StoreAccesTemporaireRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $definitif = ($donnees['mode'] ?? AccesTemporaires::MODE_TEMPORAIRE) === AccesTemporaires::MODE_DEFINITIF;

        try {
            $grant = $this->acces->accorder(
                User::findOrFail($donnees['user_id']),
                $donnees['permission'],
                isset($donnees['debut']) ? Carbon::parse($donnees['debut']) : now(),
                $definitif ? null : Carbon::parse($donnees['fin']),
                $donnees['motif'],
                $request->user(),
                $definitif,
            );
        } catch (AccesTemporaireRefuse $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $definitif
                ? 'Accès individuel définitif accordé. Il restera actif jusqu’à son retrait.'
                : 'Accès accordé jusqu\'au '.$grant->expires_at->format('d/m/Y à H:i').'.',
            'acces' => $this->listeHistoriqueRecente(),
            'statistiques' => $this->statistiques(),
        ], 201);
    }

    public function destroy(Request $request, TemporaryPermissionGrant $grant): JsonResponse
    {
        try {
            $this->acces->retirer($grant, $request->user());
        } catch (AccesTemporaireRefuse $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['message' => 'Accès retiré.', 'acces' => $this->listeHistoriqueRecente(), 'statistiques' => $this->statistiques()]);
    }

    /** @return array<string, string> nom => « Groupe — Libellé » */
    private function permissionsAccordables(): array
    {
        return $this->registry->all()
            ->filter(fn ($meta, $nom) => $this->acces->estAccordable($nom))
            ->map(fn ($meta, $nom) => ($meta['group'] ?? 'Autres').' — '.($meta['label'] ?? $nom))
            ->sort()
            ->all();
    }

    /** Liste paginee, sans N+1 et sans masquer les anciens acces actifs. */
    private function liste(string $scope, int $page): array
    {
        $query = TemporaryPermissionGrant::query()
            ->with(['user:id,name', 'grantedBy:id,name', 'revokedBy:id,name']);

        if ($scope === 'definitifs') {
            $query->actives()->where('is_permanent', true);
        } elseif ($scope === 'en_cours') {
            $query->whereNull('revoked_at')->where(function ($q) {
                $q->where('starts_at', '>', now())
                    ->orWhere(fn ($actifs) => $actifs->actives());
            });
        }

        $resultats = $query->latest('id')->paginate(30, ['*'], 'page', $page);

        return [
            'acces' => $resultats->getCollection()->map(fn (TemporaryPermissionGrant $g) => $this->serialiser($g))->all(),
            'pagination' => [
                'page' => $resultats->currentPage(),
                'pages' => $resultats->lastPage(),
                'total' => $resultats->total(),
            ],
        ];
    }

    private function statistiques(): array
    {
        return [
            'actifs' => TemporaryPermissionGrant::actives()->count(),
            'definitifs' => TemporaryPermissionGrant::actives()->where('is_permanent', true)->count(),
            'a_venir' => TemporaryPermissionGrant::whereNull('revoked_at')->where('starts_at', '>', now())->count(),
            'total' => TemporaryPermissionGrant::count(),
        ];
    }

    /** Compatibilite pour les integrateurs qui attendent un tableau `acces`. */
    private function listeHistoriqueRecente(): array
    {
        return TemporaryPermissionGrant::query()
            ->with(['user:id,name', 'grantedBy:id,name', 'revokedBy:id,name'])
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (TemporaryPermissionGrant $g) => $this->serialiser($g))
            ->all();
    }

    private function serialiser(TemporaryPermissionGrant $g): array
    {
        return [
            'id' => $g->id,
            'personne' => $g->user?->name ?? '—',
            'permission' => $g->permission,
            'libelle' => $this->registry->permissionMeta($g->permission)['label'] ?? $g->permission,
            'debut' => $g->starts_at?->format('d/m/Y H:i'),
            'fin' => $g->expires_at?->format('d/m/Y H:i'),
            'mode' => $g->is_permanent ? AccesTemporaires::MODE_DEFINITIF : AccesTemporaires::MODE_TEMPORAIRE,
            'restant' => $g->estActive() && ! $g->is_permanent
                ? $g->expires_at->diffForHumans(['parts' => 2, 'syntax' => Carbon::DIFF_RELATIVE_TO_NOW]) : null,
            'statut' => $g->statut(),
            'motif' => $g->motif,
            'accorde_par' => $g->grantedBy?->name,
            'retire_par' => $g->revokedBy?->name,
        ];
    }
}
