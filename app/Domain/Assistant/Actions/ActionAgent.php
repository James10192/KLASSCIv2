<?php

namespace App\Domain\Assistant\Actions;

use App\Services\Chatbot\Tools\ChatbotTool;

/**
 * Une action que l'assistant peut PROPOSER. L'outil déclaré au modèle ne fait
 * que préparer : il rend une proposition, que l'utilisateur valide ou refuse.
 * L'écriture passe par ExecutionDesPropositions, qui revérifie tout.
 *
 * Règles communes, tenues ici plutôt que dans chaque action :
 *  - la permission de l'outil (config/chatbot.php) est revérifiée à la validation ;
 *  - une proposition incomplète n'est jamais enregistrée : le modèle reçoit les
 *    manques et doit poser la question ;
 *  - l'exécution relit l'état et refuse si l'empreinte a changé.
 */
abstract class ActionAgent extends ChatbotTool
{
    /** Clé stable de l'action (enregistrée dans chatbot_actions_log.action_type). */
    abstract public function cle(): string;

    abstract public function preparer(array $args, $user): Proposition;

    /**
     * Écrit, par le chemin canonique de l'application. Appelée seulement après
     * validation, sur une proposition fraîchement recalculée et identique.
     *
     * @return array{message: string, lien?: ?string, model_type?: ?string, model_id?: ?int, details?: array}
     */
    abstract public function executer(Proposition $proposition, $user): array;

    public function name(): string
    {
        return 'proposer_' . $this->cle();
    }

    public function isAvailableFor($user): bool
    {
        return (bool) config('assistant.actions.actives', true) && parent::isAvailableFor($user);
    }

    final public function execute(array $args, $user): array
    {
        return app(ExecutionDesPropositions::class)->proposer($this, $args, $user);
    }
}
