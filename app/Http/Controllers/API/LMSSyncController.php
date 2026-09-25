<?php

namespace App\Http\Controllers\API;

use App\Domain\Lms\Synchronisation\CurseurDeSynchronisation;
use App\Domain\Lms\Synchronisation\FluxDeSynchronisation;
use App\Domain\Lms\Synchronisation\SynchronisationLms;
use App\Models\ESBTPAnneeUniversitaire;
use App\Support\Lms\JetonServeurLms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * GET /api/lms/v2/sync — « ce qui a change depuis », tous types confondus.
 * Reserve au jeton serveur du LMS (droit lms:lecture). Voir
 * docs/api/LMS_SYNCHRONISATION.md.
 */
class LMSSyncController extends BaseApiController
{
    public const LIMITE_MAX = 500;

    public function sync(Request $request, SynchronisationLms $synchronisation): JsonResponse
    {
        if (! JetonServeurLms::peut($request->user(), JetonServeurLms::LECTURE)) {
            return $this->errorResponse('Réservé au jeton serveur du LMS doté du droit lms:lecture.', [], 403);
        }

        $v = $request->validate([
            'since' => 'nullable|string|max:4000',
            'types' => 'nullable|string|max:200',
            'limit' => 'nullable|integer|min:1|max:'.self::LIMITE_MAX,
            'annee_universitaire_id' => 'nullable|integer|exists:esbtp_annee_universitaires,id',
        ]);

        $types = $this->types($v['types'] ?? null);
        if (is_string($types)) {
            return $this->errorResponse($types, [], 422);
        }

        $curseur = $this->curseur($v);
        if (is_string($curseur)) {
            return $this->errorResponse($curseur, [], 422);
        }

        $page = $synchronisation->page($types, $curseur, (int) ($v['limit'] ?? self::LIMITE_MAX));
        $corps = [
            'changements' => $page['changements'],
            'curseur' => $page['curseur']->encoder(),
            'a_suivre' => $page['a_suivre'],
        ];

        // L'annee courante a cote de celle du curseur : a la rentree, le LMS
        // voit qu'elles different et recommence sans « since ».
        return $this->successResponse($corps + ['nombre' => count($corps['changements'])], '', [
            'annee_universitaire_id' => $curseur->anneeId,
            'annee_courante_id' => ESBTPAnneeUniversitaire::anneeCourante()?->id,
        ]);
    }

    /** @return array<int, string>|string les types, ou la raison du refus */
    private function types(?string $brut): array|string
    {
        if ($brut === null || trim($brut) === '') {
            return FluxDeSynchronisation::TYPES;
        }

        $types = array_values(array_unique(array_filter(array_map('trim', explode(',', $brut)))));
        $inconnus = array_diff($types, FluxDeSynchronisation::TYPES);

        return $inconnus === [] && $types !== []
            ? $types
            : 'Types inconnus : '.implode(', ', $inconnus).'. Acceptés : '.implode(', ', FluxDeSynchronisation::TYPES).'.';
    }

    /** @return CurseurDeSynchronisation|string le curseur, ou la raison du refus */
    private function curseur(array $v): CurseurDeSynchronisation|string
    {
        $anneeDemandee = isset($v['annee_universitaire_id']) ? (int) $v['annee_universitaire_id'] : null;

        if (! empty($v['since'])) {
            try {
                $curseur = CurseurDeSynchronisation::decoder($v['since']);
            } catch (InvalidArgumentException $e) {
                return $e->getMessage();
            }

            return $anneeDemandee !== null && $anneeDemandee !== $curseur->anneeId
                ? "Ce curseur suit une autre année universitaire : recommencez sans « since » pour changer d'année."
                : $curseur;
        }

        $anneeId = $anneeDemandee ?? ESBTPAnneeUniversitaire::anneeCourante()?->id;
        if ($anneeId === null) {
            return 'Aucune année universitaire courante : précisez annee_universitaire_id.';
        }

        return SynchronisationLms::curseurInitial($anneeId);
    }
}
