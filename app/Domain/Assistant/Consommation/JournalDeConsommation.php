<?php

namespace App\Domain\Assistant\Consommation;

use Illuminate\Support\Facades\Log;

/**
 * Écrit les lignes d'un compteur. Une panne ici ne doit jamais faire tomber la
 * réponse déjà donnée : elle est journalisée, pas levée.
 */
class JournalDeConsommation
{
    /** @return int[] identifiants des lignes écrites */
    public function enregistrer(Compteur $compteur, ?int $userId, ?int $conversationId, string $fonction, ?string $palier, string $statut): array
    {
        $ids = [];
        $taux = Tarifs::tauxUsdFcfa();

        try {
            foreach ($compteur->lignes() as $l) {
                $ids[] = LigneDeConsommation::create([
                    'user_id' => $userId,
                    'conversation_id' => $conversationId,
                    'fonction' => $fonction,
                    'modele' => $l['modele'],
                    'fournisseur' => $l['fournisseur'],
                    'identifiant_modele' => $l['identifiant_modele'],
                    'palier' => $palier,
                    'appels' => $l['appels'],
                    'tokens_entree' => $l['tokens_entree'],
                    'tokens_sortie' => $l['tokens_sortie'],
                    'tokens_cache' => $l['tokens_cache'],
                    'cout_usd' => round($l['cout_usd'], 6),
                    'cout_fcfa' => round($l['cout_usd'] * $taux, 2),
                    'taux_usd_fcfa' => $taux,
                    'cout_exact' => $l['cout_exact'],
                    // Un modèle abandonné pour le suivant a échoué, même si l'échange a abouti.
                    'statut' => $l['echec'] ? 'echec_fournisseur' : $statut,
                    'latence_ms' => $l['latence_ms'],
                ])->id;
            }
            app(BudgetAssistant::class)->oublier();
        } catch (\Throwable $e) {
            Log::error('assistant.consommation_non_enregistree', ['erreur' => $e->getMessage(), 'cout_usd' => $compteur->coutUsd()]);
        }

        return $ids;
    }

    /** Rattache les lignes au message enregistré ensuite. */
    public function rattacherAuMessage(array $ids, int $messageId): void
    {
        if ($ids !== []) {
            LigneDeConsommation::whereIn('id', $ids)->update(['message_id' => $messageId]);
        }
    }
}
