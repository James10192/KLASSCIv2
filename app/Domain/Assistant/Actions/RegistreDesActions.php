<?php

namespace App\Domain\Assistant\Actions;

/**
 * Les actions que l'assistant sait proposer. Une action absente d'ici n'existe
 * pas pour lui ; chacune est en plus soumise à sa permission (config/chatbot.php).
 */
class RegistreDesActions
{
    /** @return ActionAgent[] */
    public function toutes(): array
    {
        return array_map(fn (string $classe) => app($classe), (array) config('assistant.actions.classes', []));
    }

    public function action(string $cle): ?ActionAgent
    {
        foreach ($this->toutes() as $action) {
            if ($action->cle() === $cle) {
                return $action;
            }
        }

        return null;
    }
}
