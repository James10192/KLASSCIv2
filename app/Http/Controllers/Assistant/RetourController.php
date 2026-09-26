<?php

namespace App\Http\Controllers\Assistant;

use App\Domain\Assistant\Retours\EnregistrerRetour;
use App\Domain\Assistant\Retours\RetourDeReponse;
use App\Domain\Assistant\Retours\SignalerReponse;
use App\Domain\Support\Services\DisponibiliteSupport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Support\Concerns\RepondAUnSignalement;
use App\Models\ChatbotMessage;
use App\Services\Care\ClientMasterSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** 👍 / 👎 sur une réponse de l'assistant, et « Signaler à KLASSCI Care ». */
class RetourController extends Controller
{
    use RepondAUnSignalement;

    public function enregistrer(Request $request, ChatbotMessage $message, EnregistrerRetour $enregistrer): JsonResponse
    {
        $this->verifierProprietaire($request, $message);
        $donnees = $request->validate([
            'avis' => ['required', Rule::in([RetourDeReponse::UTILE, RetourDeReponse::PAS_UTILE])],
            'raison' => ['nullable', Rule::in(array_keys(RetourDeReponse::RAISONS))],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ]);

        $retour = $enregistrer->executer($message, $request->user(), $donnees['avis'], $donnees['raison'] ?? null, $donnees['commentaire'] ?? null);

        return response()->json(['avis' => $retour->avis, 'raison' => $retour->raison, 'care_reference' => $retour->care_reference]);
    }

    public function signaler(Request $request, ChatbotMessage $message, SignalerReponse $signaler, DisponibiliteSupport $disponibilite): JsonResponse
    {
        $this->verifierProprietaire($request, $message);
        abort_unless($disponibilite->signalement(), 404);

        $l = app(ClientMasterSupport::class)->limites();
        $donnees = $request->validate([
            'description' => ['required', 'string', 'min:' . $l['description_min'], 'max:' . ($l['description_max'] - SignalerReponse::PLACE_DES_REPERES)],
            'cle' => ['required', 'string', 'regex:/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'],
            'contexte' => ['nullable', 'array'],
        ]);

        return $this->repondreAuSignalement(
            fn () => $signaler->executer($message, $request->user(), $donnees['description'], $donnees['cle'], (array) ($donnees['contexte'] ?? []), $request),
            $disponibilite
        );
    }

    /** Seule la personne de la conversation donne un avis, et seulement sur une réponse. */
    private function verifierProprietaire(Request $request, ChatbotMessage $message): void
    {
        abort_unless($message->role === 'assistant' && (int) $message->conversation?->user_id === (int) $request->user()->id, 404);
    }
}
