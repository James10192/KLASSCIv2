<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Deployment\CleEnvAutorisee;
use App\Services\Deployment\EnvFileWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pose a distance les secrets d'integration de l'instance.
 *
 * Il n'y a pas de SSH vers l'hebergement, et poser un secret a la main dans le
 * terminal cPanel de six ecoles est une source d'erreur en soi. Cet endpoint
 * comble ce trou — pour une liste blanche de cles, et pour elle seule.
 *
 * Deux disciplines gouvernent ce contrôleur :
 *
 * 1. La valeur ne revient JAMAIS. Ni a l'ecriture, ni a la lecture. On rend
 *    une empreinte, qui suffit a verifier que les deux cotes d'un secret
 *    partage portent bien la meme valeur, et qui ne permet pas de la
 *    reconstituer.
 * 2. Toute ecriture est journalisee en warning, avec l'auteur et la cle,
 *    jamais la valeur. Poser un secret sur une instance de production est un
 *    acte qui doit laisser une trace lisible dans le journal.
 */
class CLIEnvController extends BaseApiController
{
    /**
     * GET /api/cli/env — l'etat des cles gerables, sans leur valeur.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $ecrivain = EnvFileWriter::pourApplication();
        $cles = [];

        foreach (CleEnvAutorisee::toutes() as $cle) {
            try {
                $empreinte = $ecrivain->empreinte($cle);
            } catch (Throwable $e) {
                return $this->errorResponse($e->getMessage(), [], 500);
            }

            $cles[] = [
                'cle' => $cle,
                'definie' => $empreinte !== null,
                'empreinte' => $empreinte,
                'longueur_min' => CleEnvAutorisee::longueurMinimale($cle),
                'description' => CleEnvAutorisee::description($cle),
            ];
        }

        return $this->successResponse(['cles' => $cles]);
    }

    /**
     * POST /api/cli/env — pose une cle de la liste blanche.
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'cle' => ['required', 'string', 'max:100'],
            'valeur' => ['required', 'string', 'max:512'],
        ]);

        $cle = $valide['cle'];

        if (! CleEnvAutorisee::estAutorisee($cle)) {
            // Le message enumere les cles gerables : ce n'est pas une fuite —
            // ce sont des noms de reglages, pas des valeurs — et sans lui,
            // l'appelant ne peut pas deviner ce qu'il a le droit de poser.
            return $this->errorResponse(
                "Cle non geree par le CLI. Cles autorisees : ".implode(', ', CleEnvAutorisee::toutes()),
                [],
                422
            );
        }

        $minimum = CleEnvAutorisee::longueurMinimale($cle);

        if (strlen($valide['valeur']) < $minimum) {
            return $this->errorResponse("La valeur de {$cle} doit faire au moins {$minimum} caracteres.", [], 422);
        }

        try {
            $ecrivain = EnvFileWriter::pourApplication();
            $creee = $ecrivain->ecrire($cle, $valide['valeur']);

            // Sans cela, une configuration mise en cache continuerait de servir
            // l'ancienne valeur : le secret serait pose dans le fichier et sans
            // effet, ce qui est exactement le genre de panne qu'on ne diagnostique
            // pas — le fichier dit une chose, l'application en fait une autre.
            Artisan::call('config:clear');

            $empreinte = $ecrivain->empreinte($cle);
        } catch (Throwable $e) {
            Log::error('Ecriture .env refusee par le CLI', ['cle' => $cle, 'motif' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), [], 500);
        }

        Log::warning('Cle .env posee via le CLI', [
            'cle' => $cle,
            'empreinte' => $empreinte,
            'creee' => $creee,
            'par' => $request->user()->id,
        ]);

        return $this->successResponse([
            'cle' => $cle,
            'action' => $creee ? 'creee' : 'remplacee',
            'empreinte' => $empreinte,
        ]);
    }
}
