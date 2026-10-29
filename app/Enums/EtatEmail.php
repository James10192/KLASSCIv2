<?php

namespace App\Enums;

/**
 * Ce qu'on peut dire d'une adresse e-mail avant d'y ecrire.
 *
 * Deux degres pour les fautes de frappe, et ils ne se traitent pas pareil :
 * une faute CONNUE (`gmail.con`) est refusee, une faute PROBABLE (a deux
 * lettres d'une messagerie courante) n'est qu'une suggestion, sauf si le
 * domaine ne recoit aucun courrier.
 */
enum EtatEmail: string
{
    case Vide = 'vide';
    case Invalide = 'invalide';
    case Factice = 'factice';
    case FauteDeFrappe = 'faute_de_frappe';
    case FauteProbable = 'faute_probable';
    case SansMx = 'sans_mx';
    case Valide = 'valide';

    /** Une adresse a qui l'on peut ecrire (une faute probable dont le domaine existe en est une). */
    public function joignable(): bool
    {
        return $this === self::Valide || $this === self::FauteProbable;
    }

    /** Le type publie par le diagnostic CLI pour un domaine suspect, ou null s'il ne l'est pas. */
    public function typeSuspect(): ?string
    {
        return match ($this) {
            self::Factice => 'factice',
            self::FauteDeFrappe => 'faute_de_frappe',
            self::SansMx => 'sans_mx',
            default => null,
        };
    }
}
