<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\Assistant\Reglages\ReglagesAssistant;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Carte « Assistant IA » de /esbtp/settings : clés des fournisseurs, modèle
 * par défaut, essai réel. Réservée à system.manage comme le reste de l'écran
 * (garde posée sur le groupe de routes).
 */
class AssistantReglagesController extends Controller
{
    public function __construct(private ReglagesAssistant $reglages)
    {
    }

    public function etat(): JsonResponse
    {
        return response()->json($this->reglages->etat());
    }

    public function poserCle(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'fournisseur' => ['required', 'string', 'max:40'],
            'cle' => ['required', 'string', 'max:500'],
        ]);

        return $this->appliquer(fn () => $this->reglages->poserCle($donnees['fournisseur'], $donnees['cle'], $request->user()?->id), 'Clé enregistrée.');
    }

    public function retirerCle(Request $request, string $fournisseur): JsonResponse
    {
        return $this->appliquer(fn () => $this->reglages->retirerCle($fournisseur, $request->user()?->id), 'Clé retirée.');
    }

    public function choisirModele(Request $request): JsonResponse
    {
        $donnees = $request->validate(['modele' => ['required', 'string', 'max:60']]);

        return $this->appliquer(fn () => $this->reglages->choisirModele($donnees['modele']), 'Modèle par défaut enregistré.');
    }

    public function tester(Request $request): JsonResponse
    {
        $donnees = $request->validate(['modele' => ['nullable', 'string', 'max:60']]);

        try {
            return response()->json($this->reglages->tester($donnees['modele'] ?? null));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function appliquer(callable $action, string $message): JsonResponse
    {
        try {
            $action();
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => $message, 'etat' => $this->reglages->etat()]);
    }
}
