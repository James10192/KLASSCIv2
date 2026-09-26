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

        if ($avis === RetourDeReponse::PAS_UTILE && ($retour->wasRecentlyCreated || $retour->wasChanged('avis'))) {
            $this->monterLaConversation($message, $ligne?->palier);
        }

        return $retour;
    }

    private function monterLaConversation(ChatbotMessage $message, ?string $palierDuMessage): void
    {
        $conversation = $message->conversation;
        if (! $conversation) {
            return;
        }

        $contexte = $conversation->context ?? [];
        $base = $contexte['palier'] ?? $palierDuMessage ?? array_key_first($this->routeur->paliers());
        $au = $base ? ($this->routeur->palierAuDessus($base) ?? $base) : null;
        if ($au === null) {
            return;
        }

        $conversation->update(['context' => array_merge($contexte, ['palier' => $au, 'succes_au_palier' => 0])]);
    }
}
