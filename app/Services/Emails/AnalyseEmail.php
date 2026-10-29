<?php

namespace App\Services\Emails;

use App\Enums\EtatEmail;

/**
 * Le verdict sur une adresse. `suggestion` est l'adresse COMPLETE corrigee,
 * prete a remplacer la saisie ; elle n'existe que pour une faute de frappe.
 */
final class AnalyseEmail
{
    public function __construct(
        public readonly EtatEmail $etat,
        public readonly ?string $domaine = null,
        public readonly ?string $suggestion = null,
    ) {}

    public function joignable(): bool
    {
        return $this->etat->joignable();
    }

    /** Le domaine suggere seul (`gmail.com`), pour les agregats qui ne doivent citer aucune adresse. */
    public function domaineSuggere(): ?string
    {
        if ($this->suggestion === null) {
            return null;
        }

        $arobase = strrpos($this->suggestion, '@');

        return $arobase === false ? null : substr($this->suggestion, $arobase + 1);
    }
}
