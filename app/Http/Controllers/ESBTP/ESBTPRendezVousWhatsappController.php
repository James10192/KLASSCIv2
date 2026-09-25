<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Services\RendezVous\RelaisWhatsappConvocationRdv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * « Prevenir par WhatsApp » : un paquet borne par appel, l'ecran rappelle tant
 * qu'il en reste. Chaque famille recoit d'abord une demande d'accord ; la
 * convocation ne part qu'apres son OUI (MailPulse).
 */
class ESBTPRendezVousWhatsappController extends Controller
{
    public function envoyer(Request $request, RelaisWhatsappConvocationRdv $relais): JsonResponse
    {
        if (! $relais->actif()) {
            return response()->json(['message' => 'Le relais WhatsApp est désactivé dans les réglages.'], 422);
        }

        return response()->json($relais->envoyerUnPaquet((int) $request->user()->id));
    }
}
