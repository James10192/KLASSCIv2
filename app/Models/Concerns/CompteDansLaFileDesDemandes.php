<?php

namespace App\Models\Concerns;

use App\Domain\Admissions\FileDesDemandes;

/**
 * Les compteurs de la file des demandes (menu, bandeau) vivent une minute en
 * cache. Ils se relisent des qu'une demande est creee ou change de statut PAR
 * LE MODELE : decision sur une candidature, inscription, depot sur le portail.
 *
 * Une mise a jour par requete directe (`whereKey()->update()`) ne passe pas
 * ici : elle ne declenche aucun evenement. Les decisions de reinscription en
 * font une, a dessein (garde contre le double clic), et oublient donc les
 * compteurs elles-memes (ESBTPReinscriptionDemandeController).
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
