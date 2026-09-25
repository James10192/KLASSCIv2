<?php

namespace App\Services\Emails;

/**
 * Le DNS, derriere une interface : les tests le remplacent par un double au
 * lieu d'interroger le reseau, et la production passe par le resolveur systeme.
 */
interface ResolveurDns
{
    /** Le domaine publie-t-il un enregistrement MX, ou a defaut A ? */
    public function recoitDuCourrier(string $domaine): bool;

    /**
     * Pour une ECRITURE seulement : le domaine n'existe-t-il pas du tout ?
     * `true` uniquement si le resolveur repond NXDOMAIN, a l'instant, pour MX
     * ET pour A. `false` s'il existe. `null` si on ne sait pas (pas de
     * resolveur, delai depasse, erreur) : l'appelant n'ecrit alors rien. Jamais
     * de cache, jamais de repli, jamais de domaine temoin.
     */
    public function domaineInexistant(string $domaine): ?bool;
}
