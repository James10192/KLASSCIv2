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
 * /esbtp/acces-temporaires — donner une permission a une personne jusqu'a une date.
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
            'acces' => $this->liste(),
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json(['acces' => $this->liste()]);
    }

    public function store(StoreAccesTemporaireRequest $request): JsonResponse
    {
        $donnees = $request->validated();

        try {
            $grant = $this->acces->accorder(
                User::findOrFail($donnees['user_id']),
                $donnees['permission'],
                isset($donnees['debut']) ? Carbon::parse($donnees['debut']) : now(),
                Carbon::parse($donnees['fin']),
                $donnees['motif'],
                $request->user(),
            );
        } catch (AccesTemporaireRefuse $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Accès accordé jusqu\'au '.$grant->expires_at->format('d/m/Y à H:i').'.',
            'acces' => $this->liste(),
        ], 201);
    }

    public function destroy(Request $request, TemporaryPermissionGrant $grant): JsonResponse
    {
        $this->acces->retirer($grant, $request->user());

        return response()->json(['message' => 'Accès retiré.', 'acces' => $this->liste()]);
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

    /** @return array<int, array<string, mixed>> */
    private function liste(): array
    {
        return TemporaryPermissionGrant::query()
            ->with(['user:id,name', 'grantedBy:id,name', 'revokedBy:id,name'])
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (TemporaryPermissionGrant $g) => [
                'id' => $g->id,
                'personne' => $g->user?->name ?? '—',
                'permission' => $g->permission,
                'libelle' => $this->registry->permissionMeta($g->permission)['label'] ?? $g->permission,
                'debut' => $g->starts_at?->format('d/m/Y H:i'),
                'fin' => $g->expires_at?->format('d/m/Y H:i'),
                'restant' => $g->estActive() ? $g->expires_at->diffForHumans(['parts' => 2, 'syntax' => Carbon::DIFF_RELATIVE_TO_NOW]) : null,
                'statut' => $g->statut(),
                'motif' => $g->motif,
                'accorde_par' => $g->grantedBy?->name,
                'retire_par' => $g->revokedBy?->name,
            ])
            ->all();
    }
}
