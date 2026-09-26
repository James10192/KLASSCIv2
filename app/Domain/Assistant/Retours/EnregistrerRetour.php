<?php

namespace App\Domain\Assistant\Retours;

use App\Domain\Assistant\Consommation\LigneDeConsommation;
use App\Domain\Assistant\Routage\Routeur;
use App\Models\ChatbotMessage;
use App\Models\User;

/**
 * 👍 / 👎 sur une réponse. Un 👎 n'est pas qu'une statistique : la question
 * suivante de la conversation part un palier au-dessus, comme après un échec
 * (le routeur redescend seul après trois réussites).
 */
class EnregistrerRetour
{
    public function __construct(private Routeur $routeur)
    {
    }

    public function executer(ChatbotMessage $message, User $user, string $avis, ?string $raison, ?string $commentaire): RetourDeReponse
    {
        $ligne = LigneDeConsommation::where('message_id', $message->id)->where('fonction', 'question')->latest('id')->first();

        $retour = RetourDeReponse::updateOrCreate(
            ['message_id' => $message->id, 'user_id' => $user->id],
            [
                'conversation_id' => $message->conversation_id,
                'avis' => $avis,
                'raison' => $avis === RetourDeReponse::PAS_UTILE ? $raison : null,
                'commentaire' => $avis === RetourDeReponse::PAS_UTILE && $commentaire !== null ? mb_substr(trim($commentaire), 0, 1000) : null,
                'modele' => $ligne?->modele,
                'palier' => $ligne?->palier,
            ]
        );

        if ($avis === RetourDeReponse::PAS_UTILE) {
            $this->monterLaConversation($message, $ligne?->palier);
        }

        return $retour;
    }

    /**
     * Cible : un palier au-dessus de celui qui a produit la réponse notée, jamais
     * plus. Si la conversation y est déjà (ou plus haut), rien ne bouge, et sa
     * série de réussites n'est pas remise à zéro : cliquer 👎 à répétition, ou
     * sur de vieilles réponses, ne peut pas figer la conversation au prix fort.
     */
    private function monterLaConversation(ChatbotMessage $message, ?string $palierDuMessage): void
    {
        $conversation = $message->conversation;
        $ordre = array_keys($this->routeur->paliers());
        if (! $conversation || $ordre === []) {
            return;
        }

        $cible = $this->routeur->palierAuDessus($palierDuMessage ?? $ordre[0]);
        $contexte = $conversation->context ?? [];
        $actuel = array_search($contexte['palier'] ?? $ordre[0], $ordre, true);
        if ($cible === null || ($actuel !== false && $actuel >= array_search($cible, $ordre, true))) {
            return;
        }

        $conversation->update(['context' => array_merge($contexte, ['palier' => $cible, 'succes_au_palier' => 0])]);
    }
}
