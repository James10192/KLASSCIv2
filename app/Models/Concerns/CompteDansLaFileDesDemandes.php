<?php

namespace App\Models\Concerns;

use App\Domain\Admissions\FileDesDemandes;

/**
 * Les compteurs de la file des demandes (menu, bandeau) vivent une minute en
 * cache. Ils se relisent des qu'une demande arrive ou change de statut, quel
 * que soit le chemin : decision au guichet, inscription, depot sur le portail.
 * Poser l'oubli ici plutot qu'apres chaque decision evite d'en oublier une.
 */
trait CompteDansLaFileDesDemandes
{
    public static function bootCompteDansLaFileDesDemandes(): void
    {
        static::saved(function ($demande): void {
            if ($demande->wasRecentlyCreated || $demande->wasChanged('statut')) {
                FileDesDemandes::oublierLesCompteurs();
            }
        });
    }
}
