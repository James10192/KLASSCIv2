<?php

namespace App\Http\Controllers\Support\Concerns;

use App\Domain\Support\Exceptions\MasterSupportRefus;
use App\Domain\Support\Services\DisponibiliteSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * La réponse HTTP à un signalement KLASSCI Care, quel que soit l'écran qui l'envoie
 * (lanceur du support, assistant) : une seule traduction des refus du Master.
 */
trait RepondAUnSignalement
{
    /** @param callable(): array $envoyer rend la projection du Master, ou ['en_attente' => true] */
    protected function repondreAuSignalement(callable $envoyer, DisponibiliteSupport $disponibilite): JsonResponse
    {
        try {
            $resultat = $envoyer();
        } catch (MasterSupportRefus $e) {
            if ($e->codeErreur === 'idempotency_key_reused') {
                // Le brouillon a change depuis un envoi que le Master a bien recu :
                // le navigateur tire une cle neuve et renvoie. Pas une faute a montrer.
                return response()->json(['erreur' => 'cle_perimee'], 409);
            }

            // Un refus ici est un defaut d'integration, pas une faute de l'utilisateur :
            // on le journalise et on lui propose le courriel plutot que de lui montrer un code.
            Log::error('KLASSCI Care : signalement refusé par le Master', ['statut' => $e->statut, 'code' => $e->codeErreur, 'erreurs' => $e->erreurs]);

            return response()->json([
                'message' => "Votre demande n'a pas pu être transmise. Écrivez-nous à ".config('app.support_email').'.',
            ], 422);
        }

        if ($resultat['en_attente'] ?? false) {
            return response()->json([
                'en_attente' => true,
                'message' => 'Votre demande est enregistrée. Elle sera transmise au support dès que la connexion sera rétablie.',
            ], 202);
        }

        return response()->json([
            'reference' => $resultat['reference'] ?? null,
            'statut' => $resultat['statut']['libelle'] ?? null,
            'suivi_url' => $disponibilite->suivi() && isset($resultat['reference'])
                ? route('support.demandes.show', $resultat['reference']) : null,
        ], 201);
    }
}
