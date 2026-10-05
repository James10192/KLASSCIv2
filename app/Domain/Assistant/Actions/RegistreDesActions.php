<?php

namespace App\Domain\Assistant\Actions;

/**
 * Les actions que l'assistant sait proposer. Une action absente d'ici n'existe
 * pas pour lui ; chacune est en plus soumise à sa permission.
 */
class RegistreDesActions
{
    /** @return ActionAgent[] */
    public function toutes(): array
    {
        $classes = (array) config('assistant.actions.classes', []);

        // Lot Nanan RDV/professeurs : ces actions gardent elles-mêmes les permissions
        // métier exactes et restent disponibles pendant la propagation progressive.
        $classes[] = \App\Domain\Assistant\Actions\RendezVous\ReprogrammerRendezVous::class;
        $classes[] = \App\Domain\Assistant\Actions\RendezVous\AnnulerRendezVous::class;
        $classes[] = \App\Domain\Assistant\Actions\RendezVous\BasculerCreneauRdv::class;
        $classes[] = \App\Domain\Assistant\Actions\RendezVous\ReglerFermetureJourMinuit::class;
        $classes[] = \App\Domain\Assistant\Actions\Enseignants\ModifierEnseignant::class;
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
