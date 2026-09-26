<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Audit\FiltresDuJournal;
use App\Domain\Audit\ThemesDuJournal;
use App\Http\Controllers\API\BaseApiController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * La table du journal d'audit, vue de l'exterieur : sa taille, ce que coutent
 * les requetes du journal sur cette instance, et la purge des consultations.
 *
 * Il n'y a pas de SSH vers l'hebergement. Savoir si « Depuis le début » tient
 * sur la plus grosse ecole demandait donc de lancer la requete sur place.
 *
 * Les consultations (`retrieved`) ont ete ecrites a chaque lecture d'une fiche
 * jusqu'a leur retrait de config/audit.php. Elles ne portent ni valeur ni
 * changement, et le journal les ignore deja : la purge n'enleve aucune
 * information, seulement du volume.
 */
class CLIAuditController extends BaseApiController
{
    /** Au-dela, la requete HTTP risquerait le delai de l'hebergement : on rend la main, on relance. */
    private const SECONDES_PAR_APPEL = 20;

    /** GET /api/cli/audit/etat */
    public function etat(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $filtres = FiltresDuJournal::depuis(new Request(['periode' => 'tout']), [ThemesDuJournal::TOUT]);
        $liste = $filtres->requete()->orderByDesc('created_at')->orderByDesc('id')->limit(51);

        return $this->successResponse([
            'lignes' => DB::table('audits')->count(),
            'consultations' => DB::table('audits')->where('event', 'retrieved')->count(),
            'plus_ancienne' => DB::table('audits')->min('created_at'),
            'depuis_le_debut' => [
                'liste' => $this->mesurer($liste, fn (Builder $q) => $q->get()->count()),
                'a_regarder' => $this->mesurer(ThemesDuJournal::aRegarder($filtres->base()), fn (Builder $q) => $q->count()),
                'automatiques' => $this->mesurer($filtres->base()->whereNull('user_id'), fn (Builder $q) => $q->count()),
            ],
        ], 'Etat du journal d\'audit');
    }

    /**
     * POST /api/cli/audit/purger-consultations — { "dry": true, "lot": 5000 }
     *
     * Simulation par defaut. Supprime par lots, par identifiant croissant, et
     * s'arrete au bout de SECONDES_PAR_APPEL : `restantes` dit s'il faut relancer.
     */
    public function purgerConsultations(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $consultations = fn () => DB::table('audits')->where('event', 'retrieved');
        $avant = $consultations()->count();

        if ($request->boolean('dry', true)) {
            return $this->successResponse(['simulation' => true, 'a_supprimer' => $avant], 'Simulation : rien n\'a ete supprime');
        }

        $lot = max(500, min((int) $request->input('lot', 5000), 20000));
        $fin = microtime(true) + self::SECONDES_PAR_APPEL;
        $supprimees = 0;
        $apres = 0;
        do {
            // Chaque lot reprend apres le precedent : sans cette borne, chaque DELETE
            // relirait (et verrouillerait) toutes les lignes gardees depuis l'id 1.
            $ids = $consultations()->where('id', '>', $apres)->orderBy('id')->limit($lot)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $apres = $ids->last();
            $supprimees += DB::table('audits')->whereIn('id', $ids)->delete();
        } while (microtime(true) < $fin);

        $restantes = $consultations()->count();
        Log::warning('[cli/audit] consultations purgees', ['supprimees' => $supprimees, 'restantes' => $restantes, 'par' => $request->user()->id]);

        return $this->successResponse(['simulation' => false, 'supprimees' => $supprimees, 'restantes' => $restantes],
            $restantes > 0 ? 'Purge partielle : relancer' : 'Purge terminee');
    }

    /** Le plan MySQL et la duree reelle d'une requete du journal. */
    private function mesurer(Builder $requete, \Closure $executer): array
    {
        $plan = DB::select('EXPLAIN '.$requete->toSql(), $requete->getBindings());
        $debut = microtime(true);
        $resultat = $executer($requete);

        return [
            'ms' => (int) round((microtime(true) - $debut) * 1000),
            'resultat' => $resultat,
            'plan' => array_map(fn ($l) => array_intersect_key((array) $l, array_flip(['type', 'key', 'rows', 'Extra'])), $plan),
        ];
    }
}
