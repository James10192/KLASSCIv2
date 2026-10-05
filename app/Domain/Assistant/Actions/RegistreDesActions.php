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
        $classes = (array) config('assistant.actions.classes', []);

        // Reprogrammation administrative des rendez-vous : ajoutée ici pour
        // rester disponible pendant le déploiement progressif du lot Nanan RDV.
        // La classe garde elle-même la permission canonique inscriptions.rdv.manage.
        $classes[] = \App\Domain\Assistant\Actions\RendezVous\ReprogrammerRendezVous::class;
        $classes = array_values(array_unique($classes));

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
