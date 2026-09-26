<?php

namespace App\Http\Controllers\Assistant;

use App\Domain\Assistant\Retours\EnregistrerRetour;
use App\Domain\Assistant\Retours\RetourDeReponse;
use App\Domain\Assistant\Retours\SignalerReponse;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use App\Domain\Support\Services\DisponibiliteSupport;
use App\Http\Controllers\Controller;
use App\Models\ChatbotMessage;
use App\Services\Care\ClientMasterSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/** 👍 / 👎 sur une réponse de l'assistant, et « Signaler à KLASSCI Care ». */
class RetourController extends Controller
{
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
            'description' => ['required', 'string', 'min:' . $l['description_min'], 'max:' . ($l['description_max'] - 200)],
            'cle' => ['required', 'string', 'regex:/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'],
            'contexte' => ['nullable', 'array'],
        ]);

        try {
            $resultat = $signaler->executer($message, $request->user(), $donnees['description'], $donnees['cle'], (array) ($donnees['contexte'] ?? []), $request);
        } catch (MasterSupportRefus $e) {
            if ($e->codeErreur === 'idempotency_key_reused') {
                return response()->json(['erreur' => 'cle_perimee'], 409);
            }
            Log::error('assistant.signalement_refuse', ['statut' => $e->statut, 'code' => $e->codeErreur]);

            return response()->json(['message' => "Le signalement n'a pas pu être transmis. Écrivez-nous à " . config('app.support_email') . '.'], 422);
        }

        if ($resultat['en_attente'] ?? false) {
            return response()->json(['en_attente' => true, 'message' => 'Signalement enregistré : il partira au support dès que la connexion sera rétablie.'], 202);
        }

        return response()->json([
            'reference' => $resultat['reference'] ?? null,
            'message' => 'Merci : le support a reçu votre signalement' . (isset($resultat['reference']) ? ' (' . $resultat['reference'] . ')' : '') . '.',
            'suivi_url' => $disponibilite->suivi() && isset($resultat['reference']) ? route('support.demandes.show', $resultat['reference'], false) : null,
        ], 201);
    }

    /** Seule la personne de la conversation donne un avis, et seulement sur une réponse. */
    private function verifierProprietaire(Request $request, ChatbotMessage $message): void
    {
        abort_unless($message->role === 'assistant' && (int) $message->conversation?->user_id === (int) $request->user()->id, 404);
    }
}
