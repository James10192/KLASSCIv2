<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Services\Deployment\CleEnvAutorisee;
use App\Services\Deployment\EnvFileWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RuntimeException;
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

            $entree = [
                'cle' => $cle,
                'definie' => $empreinte !== null,
                'empreinte' => $empreinte,
                'longueur_min' => CleEnvAutorisee::longueurMinimale($cle),
                'description' => CleEnvAutorisee::description($cle),
            ];

            // Une empreinte ne sert a rien pour un identifiant : ce qu'on veut
            // savoir, c'est sous quel code l'instance se declare. La valeur
            // n'est publiee que pour les cles explicitement non secretes.
            if (! CleEnvAutorisee::estSecrete($cle)) {
                $entree['valeur'] = $ecrivain->valeur($cle);
                $entree['format_attendu'] = CleEnvAutorisee::formatLisible($cle);
            }

            $cles[] = $entree;
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
            // Jeton opaque : ni espace, ni retour a la ligne, ni caractere de
            // controle. Un retour a la ligne dans la valeur permettait, en deux
            // appels, d'ecrire une cle que la liste blanche interdit. La garde
            // vit AUSSI dans EnvFileWriter — ici pour rendre un 422 lisible,
            // la-bas parce qu'un service ne fait pas confiance a son appelant.
            'valeur' => ['required', 'string', 'max:512', 'regex:/^[A-Za-z0-9_\-.:\/+=~]+$/D'],
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

        // Un identifiant se controle par sa forme, pas par sa longueur :
        // « esbtp-abidjan » pose sur une instance qui n'est pas Abidjan fait
        // exactement la bonne longueur. Le format ne dit pas que le code est le
        // BON — cela, seul « tenant:verifier-identite » le confronte a l'hote et
        // a la base — mais il ecarte ce qui ne peut etre un code du tout.
        if (! CleEnvAutorisee::respecteFormat($cle, $valide['valeur'])) {
            return $this->errorResponse(
                sprintf('La valeur de %s ne respecte pas le format attendu : %s.', $cle, CleEnvAutorisee::formatLisible($cle)),
                [],
                422
            );
        }

        try {
            $ecrivain = EnvFileWriter::pourApplication();
            $creee = $ecrivain->ecrire($cle, $valide['valeur']);

            // Sans cela, une configuration mise en cache continuerait de servir
            // l'ancienne valeur : le secret serait pose dans le fichier et sans
            // effet, ce qui est exactement le genre de panne qu'on ne diagnostique
            // pas — le fichier dit une chose, l'application en fait une autre.
            //
            // Le code de retour est verifie, et le fichier de cache aussi :
            // ConfigClearCommand ignore le booleen de Filesystem::delete, donc
            // un fichier non supprimable ne leve rien et ne retourne pas
            // d'erreur. On repondrait « pose » sur une valeur sans effet — la
            // panne muette que ce bloc existe justement pour empecher.
            if (Artisan::call('config:clear') !== 0 || is_file(base_path('bootstrap/cache/config.php'))) {
                throw new RuntimeException(
                    'Cle ecrite, mais le cache de configuration n\'a pas pu etre purge : '
                    .'la valeur posee reste sans effet. Purgez le cache sur le serveur.'
                );
            }

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
