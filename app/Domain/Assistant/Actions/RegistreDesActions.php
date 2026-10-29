<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Actions\Enseignants\ModifierProfilEnseignant;

/**
 * Les actions que l'assistant sait proposer. Une action absente d'ici n'existe
 * pas pour lui ; chacune est en plus soumise à sa permission (config/chatbot.php
 * ou garde explicite de l'action pour les capacités ajoutées progressivement).
 */
class RegistreDesActions
{
    /** @return ActionAgent[] */
    public function toutes(): array
    {
        $classes = array_values(array_unique(array_merge(
            (array) config('assistant.actions.classes', []),
            [
                ModifierProfilEnseignant::class,
                // La reprogrammation administrative en masse reste disponible en
                // complément de la gestion ciblée des rendez-vous.
                \App\Domain\Assistant\Actions\RendezVous\ReprogrammerRendezVous::class,
            ],
        )));

        return array_map(fn (string $classe) => app($classe), $classes);
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
