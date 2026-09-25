<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ParentChatbot\ParentChatbotInboundSignature;
use App\Services\RendezVous\RelaisWhatsappConvocationRdv;
use App\Services\RendezVous\SuiviWhatsappConvocationRdv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Les comptes rendus MailPulse d'un envoi soumis a consentement
 * (`consent.*`, `message.*`), sur le meme callback signe que le chatbot
 * parent : meme cle, meme secret, meme verification.
 *
 * Un evenement qu'aucune reservation ne reconnait est accuse (202) sans effet :
 * le faire rejouer par MailPulse ne le rendrait pas plus reconnaissable.
 */
class MailPulseEvenementsController extends Controller
{
    public function __invoke(
        Request $request,
        ParentChatbotInboundSignature $signature,
        SuiviWhatsappConvocationRdv $suivi,
    ): JsonResponse {
        if (! $signature->isValid($request)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $evenement = $request->validate([
            'event' => ['required', 'string', Rule::in(SuiviWhatsappConvocationRdv::evenements())],
            'operationKey' => ['required', 'string', 'max:128'],
            'idempotencyKey' => ['required', 'string', 'max:128'],
            'operationId' => ['nullable', 'string', 'max:100'],
            'occurredAt' => ['nullable', 'string', 'max:40'],
            'failureCode' => ['nullable', 'string', 'max:100'],
            'messageId' => ['nullable', 'string', 'max:200'],
        ]);

        if ($evenement['operationKey'] !== RelaisWhatsappConvocationRdv::OPERATION) {
            return response()->json(['accepted' => true, 'applied' => false], 202);
        }

        $issue = $suivi->appliquer($evenement);
        if ($issue === SuiviWhatsappConvocationRdv::INCONNU) {
            Log::warning('Convocation rdv WhatsApp : evenement sans reservation', ['event' => $evenement['event']]);
        }

        return response()->json([
            'accepted' => true,
            'applied' => $issue === SuiviWhatsappConvocationRdv::APPLIQUE,
            'duplicate' => $issue === SuiviWhatsappConvocationRdv::DEJA_VU,
        ], 202);
    }
}
