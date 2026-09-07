<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Le diagnostic du portail de reinscription, joignable a distance.
 *
 * Il existe parce que le serveur de production n'est pas atteignable en SSH :
 * sans ce point d'entree, la seule facon de savoir pourquoi une famille est
 * bloquee serait d'ouvrir un terminal cPanel — ce qu'on ne fait pas avec une
 * scolarite au telephone.
 *
 * `cli:admin` et non `cli:read` : la reponse nomme la date de naissance d'un
 * etudiant, c'est-a-dire la moitie du facteur d'identification du portail. Un
 * jeton de lecture large n'a pas a la servir.
 */
class CLIReinscriptionPortailController extends BaseApiController
{
    public function diagnose(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'matricule' => ['required', 'string', 'max:50'],
            // Meme severite que le portail : diagnostiquer un format qu'il
            // refuse reviendrait a diagnostiquer autre chose que le probleme.
            'date_naissance' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        try {
            $parametres = ['matricule' => $valide['matricule'], '--json' => true];

            if (isset($valide['date_naissance'])) {
                $parametres['--date'] = $valide['date_naissance'];
            }

            $code = Artisan::call('reinscription:portail-diagnose', $parametres);

            // Lue UNE fois : la sortie tamponnee de Laravel se vide a la
            // lecture, donc un second Artisan::output() rendrait une chaine
            // vide — soit precisement le diagnostic d'echec ampute de ce qui
            // permettait de comprendre l'echec.
            $sortie = Artisan::output();
            $rapport = json_decode($sortie, true);

            if ($code !== 0 || ! is_array($rapport)) {
                return $this->errorResponse('Reinscription portal diagnosis failed.', [
                    'exit_code' => $code,
                    'output' => Str::limit(trim($sortie), 2000),
                ], 500);
            }

            return $this->successResponse($rapport, 'Reinscription portal diagnosis completed');
        } catch (\Throwable $exception) {
            Log::error('CLI: reinscription portal diagnosis failed', [
                'error' => $exception->getMessage(),
            ]);

            return $this->errorResponse('Reinscription portal diagnosis failed. Check server logs.', [], 500);
        }
    }
}
