<?php

namespace App\Domain\Assistant\Retours;

use App\Domain\Support\Actions\SoumettreDemande;
use App\Domain\Support\Services\ContexteDePage;
use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * « Signaler à KLASSCI Care » depuis une réponse de l'assistant.
 *
 * La description est celle que la personne a relue et envoyée : le navigateur
 * la préremplit avec sa question et un extrait de la réponse, qu'elle peut
 * retirer. Le serveur n'y ajoute que des repères techniques sans donnée
 * d'élève (conversation, message, modèle), pour que le support retrouve
 * l'échange dans les journaux.
 */
class SignalerReponse
{
    public function __construct(private SoumettreDemande $soumettre)
    {
    }

    public function executer(ChatbotMessage $message, User $user, string $description, string $cle, array $contexteBrut, Request $request): array
    {
        $retour = RetourDeReponse::firstOrNew(['message_id' => $message->id, 'user_id' => $user->id]);
        $categorie = ($retour->raison ?? null) === 'faux' ? 'INFORMATION_INCORRECTE' : 'PROBLEME';

        $reperes = sprintf(
            "\n\n—\nRéponse de l'assistant n° %d, conversation %s%s.",
            $message->id,
            $message->conversation?->session_id ?? '?',
            $retour->modele ? ', modèle ' . $retour->modele : ''
        );

        $contexte = ContexteDePage::assainir(array_merge($contexteBrut, ['extras' => ['composant' => 'assistant']]), $request);
        $resultat = $this->soumettre->executer($user, $categorie, trim($description) . $reperes, $contexte, $cle, $request->attributes->get('request_id'));

        if (! empty($resultat['reference'])) {
            $retour->fill([
                'conversation_id' => $message->conversation_id,
                'avis' => $retour->avis ?? RetourDeReponse::PAS_UTILE,
                'care_reference' => mb_substr((string) $resultat['reference'], 0, 64),
            ])->save();
        }

        return $resultat;
    }
}
