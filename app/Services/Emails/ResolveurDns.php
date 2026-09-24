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
}
