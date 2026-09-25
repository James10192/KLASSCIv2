<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Assistant\Reglages\ReglagesAssistant;
use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Réglages de l'assistant IA depuis klassci-cli : mêmes actions que la carte
 * « Assistant IA » de /esbtp/settings (ReglagesAssistant). La clé part vers
 * l'école et n'en revient jamais : seul l'état est renvoyé.
 */
class CLIAssistantController extends BaseApiController
{
    public function __construct(private ReglagesAssistant $reglages)
    {
    }

    public function etat(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read') && ! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        return $this->successResponse($this->reglages->etat());
    }

    public function poserCle(Request $request): JsonResponse
    {
        if ($refus = $this->refuserSansAdmin($request)) {
            return $refus;
        }
        $donnees = $request->validate([
            'fournisseur' => ['required', 'string', 'max:40'],
            'cle' => ['required', 'string', 'max:500'],
        ]);

        return $this->appliquer(fn () => $this->reglages->poserCle($donnees['fournisseur'], $donnees['cle'], null), 'Clé enregistrée (chiffrée).');
    }

    public function retirerCle(Request $request, string $fournisseur): JsonResponse
    {
        if ($refus = $this->refuserSansAdmin($request)) {
            return $refus;
        }

        return $this->appliquer(fn () => $this->reglages->retirerCle($fournisseur, null), 'Clé retirée.');
    }

    public function choisirModele(Request $request): JsonResponse
    {
        if ($refus = $this->refuserSansAdmin($request)) {
            return $refus;
        }
        $donnees = $request->validate(['modele' => ['required', 'string', 'max:60']]);

        return $this->appliquer(fn () => $this->reglages->choisirModele($donnees['modele']), 'Modèle par défaut enregistré.');
    }

    public function tester(Request $request): JsonResponse
    {
        if ($refus = $this->refuserSansAdmin($request)) {
            return $refus;
        }
        $donnees = $request->validate(['modele' => ['nullable', 'string', 'max:60']]);

        try {
            return $this->successResponse($this->reglages->tester($donnees['modele'] ?? null));
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        }
    }

    private function appliquer(callable $action, string $message): JsonResponse
    {
        try {
            $action();
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        }

        return $this->successResponse($this->reglages->etat(), $message);
    }

    private function refuserSansAdmin(Request $request): ?JsonResponse
    {
        return $request->user()->tokenCan('cli:admin')
            ? null
            : $this->errorResponse('Token missing cli:admin ability', [], 403);
    }
}
