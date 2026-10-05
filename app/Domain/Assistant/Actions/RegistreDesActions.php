<?php

namespace App\Domain\Assistant\Actions;

/**
 * Les actions que l'assistant sait proposer. Une action absente des registres
 * n'existe pas pour lui ; chacune reste soumise à sa permission d'outil.
 */
class RegistreDesActions
{
    /** @return ActionAgent[] */
    public function toutes(): array
    {
        $classes = array_values(array_unique(array_merge(
            (array) config('assistant.actions.classes', []),
            (array) config('assistant_tools_nanan.actions', []),
        )));

        return array_map(fn (string $classe) => app($classe), $classes);
    }

    public function action(string $cle): ?ActionAgent
    {
        foreach ($this->toutes() as $action) {
            if ($action->cle() === $cle) return $action;
        }
        return null;
    }
}
