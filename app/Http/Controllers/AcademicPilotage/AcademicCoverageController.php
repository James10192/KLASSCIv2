<?php

declare(strict_types=1);

namespace App\Http\Controllers\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicActorScopeService;
use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use App\Http\Controllers\Controller;
use App\Models\ESBTPClasse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * La couverture des notes d'une classe, servie a la demande.
 *
 * Separe du tableau de bord : celui-ci recalcule tout a chaque appel, et
 * demander la couverture d'une classe ne doit pas payer le reste.
 */
class AcademicCoverageController extends Controller
{
    /** Le temps qu'une saisie en cours reste raisonnablement visible. */
    private const TTL = 600;

    public function __construct(
        private readonly AcademicNoteCoverageService $coverage,
        private readonly AcademicActorScopeService $scope,
    ) {}

    public function show(Request $request, ESBTPClasse $classe): JsonResponse
    {
        $anneeId = $request->integer('annee_universitaire_id') ?: null;
        $periode = (string) ($request->input('periode') ?: 'semestre1');

        // LE PERIMETRE D'ABORD, LE CACHE ENSUITE. Lire le cache avant de
        // verifier qui demande servirait a un acteur hors perimetre le contenu
        // mis en cache par un autre. La cle ne porte pas d'identite parce que
        // le contenu n'en depend pas : elle n'a donc rien pour rattraper un
        // controle d'acces oublie.
        $perimetre = $this->scope->dashboardScope($request->user(), $anneeId);
        $classesAutorisees = $perimetre->global ? null : $perimetre->classIds;

        abort_if(
            $classesAutorisees !== null && ! $classesAutorisees->contains((int) $classe->id),
            403,
            'Cette classe est hors de votre périmètre.'
        );

        if ($anneeId === null) {
            return response()->json($this->coverage->summarize(null, $periode, null, (int) $classe->id), 200);
        }

        $cle = $this->cle((int) $classe->id, $anneeId, $periode);

        if ($request->boolean('recalculer')) {
            Cache::forget($cle);
        }

        $payload = Cache::remember(
            $cle,
            self::TTL,
            fn () => $this->coverage->summarize($anneeId, $periode, null, (int) $classe->id)
        );

        return response()->json($payload, 200);
    }

    /**
     * La cle du cache.
     *
     * Le contenu ne depend que de la classe, de l'annee et de la periode —
     * jamais de qui regarde. Le prefixe de cache est deja propre a chaque
     * instance (`CACHE_PREFIX`), l'isolation entre ecoles est donc acquise.
     */
    public static function cle(int $classeId, int $anneeId, string $periode): string
    {
        return 'pilotage.couverture.'.$classeId.'.'.$anneeId.'.'.mb_strtolower($periode, 'UTF-8');
    }
}
