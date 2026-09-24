<?php

namespace App\Services\Emails\Fautes;

use App\Enums\EtatEmail;
use App\Services\Emails\DiagnosticEmail;

/**
 * Le seul domaine vers lequel une faute de frappe peut etre corrigee : la
 * suggestion des listes partagees (`DomainesSuspects`), pour un domaine
 * classe faute de frappe CERTAINE (liste des fautes connues, ou faute
 * probable dont le domaine ne recoit aucun courrier). Jamais un domaine
 * fourni par l'appelant.
 */
class DomaineCanonique
{
    public function __construct(private readonly DiagnosticEmail $classement) {}

    public function pour(string $domaine, bool $avecMx = true): ?string
    {
        $domaine = mb_strtolower(trim($domaine));
        if ($domaine === '') {
            return null;
        }
        $analyse = $this->classement->classerDomaine($domaine, $avecMx);

        return $analyse->etat === EtatEmail::FauteDeFrappe ? $analyse->domaineSuggere() : null;
    }

    public static function domaineDe(?string $email): string
    {
        $email = trim((string) $email);
        $arobase = strrpos($email, '@');

        return $arobase === false ? '' : mb_strtolower(substr($email, $arobase + 1));
    }

    /** La partie locale telle quelle, le domaine remplace. */
    public static function corriger(string $email, string $domaine): string
    {
        $email = trim($email);

        return substr($email, 0, (int) strrpos($email, '@')).'@'.$domaine;
    }
}
