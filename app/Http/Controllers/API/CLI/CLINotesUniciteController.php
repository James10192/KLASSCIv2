<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Notes\UniciteDesNotes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le pendant CLI de `php artisan notes:unicite`, pour les serveurs sans SSH.
 *
 * La migration ne pose pas l'unicité sur une instance qui porte déjà des notes
 * en double, et elle ne repassera pas : c'est ici qu'on liste les doublons à
 * trancher, puis qu'on pose l'unicité une fois la liste vide. Aucune note n'est
 * jamais effacée d'ici.
 */
class CLINotesUniciteController extends BaseApiController
{
    public function __construct(private UniciteDesNotes $unicite)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }
        if (! $this->unicite->disponible()) {
            return $this->errorResponse('Unicité disponible sur MySQL uniquement.', [], 422);
        }

        return $this->successResponse($this->etat());
    }

    public function poser(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }
        if (! $this->unicite->disponible()) {
            return $this->errorResponse('Unicité disponible sur MySQL uniquement.', [], 422);
        }

        $etat = $this->etat();
        if ($etat['doublons'] !== []) {
            return $this->errorResponse(
                'Des notes en double restent à trancher : gardez la bonne, effacez les autres, puis relancez.',
                $etat,
                409
            );
        }

        // Un doublon a pu apparaître entre la lecture et la pose.
        if (! $this->unicite->poser()) {
            return $this->errorResponse('Unicité non posée : relancez le diagnostic.', $this->etat(), 409);
        }

        return $this->successResponse($this->etat(), 'Unicité en place : une seule note vivante par élève et par évaluation.');
    }

    /** @return array{index_pose: bool, doublons: list<array{etudiant_id: int, evaluation_id: int, nombre: int, note_ids: list<int>}>} */
    private function etat(): array
    {
        return [
            'index_pose' => $this->unicite->indexPose(),
            'doublons' => $this->unicite->doublons()->map(fn ($d) => [
                'etudiant_id' => (int) $d->etudiant_id,
                'evaluation_id' => (int) $d->evaluation_id,
                'nombre' => (int) $d->nombre,
                'note_ids' => array_map('intval', explode(',', $d->note_ids)),
            ])->values()->all(),
        ];
    }
}
