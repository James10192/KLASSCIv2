<?php

namespace App\Http\Controllers\Assistant;

use App\Domain\Assistant\Actions\ExecutionDesPropositions;
use App\Http\Controllers\Controller;
use App\Models\ChatbotActionLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Clic « Valider » ou « Refuser » sous une proposition de l'assistant.
 * Toute la vérification vit dans ExecutionDesPropositions ; ici, seulement le
 * contrat HTTP : 200 si c'est fait, 409 si la proposition ne peut plus l'être.
 */
class PropositionController extends Controller
{
    public function __construct(private ExecutionDesPropositions $execution)
    {
    }

    public function valider(Request $request, ChatbotActionLog $proposition): JsonResponse
    {
        $donnees = $request->validate(['jeton' => ['required', 'string', 'max:128']]);

        return $this->reponse($this->execution->valider($proposition, $request->user(), $donnees['jeton']), 'executee');
    }

    public function refuser(Request $request, ChatbotActionLog $proposition): JsonResponse
    {
        return $this->reponse($this->execution->refuser($proposition, $request->user()), 'refusee');
    }

    private function reponse(array $resultat, string $succes): JsonResponse
    {
        return response()->json($resultat, $resultat['statut'] === $succes ? 200 : 409);
    }
}
