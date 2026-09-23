<?php

namespace App\Enums;

/**
 * Ou en est la verification du contact d'une demande publique (candidature,
 * reinscription). Colonne `verification_contact`.
 *
 * NULL : demande d'avant la verification, visible comme avant.
 *
 * Les deux etats « non verifie » rendent la demande INVISIBLE pour l'ecole
 * (portee globale AttendVerificationContact) : elle n'entre dans aucune
 * liste, aucun compteur, aucune affectation de rendez-vous.
 *
 * `impossible` : le code n'a pas pu partir pour une raison qui ne tient pas a
 * la famille (MailPulse desactive, cle absente, WhatsApp indisponible). La
 * demande reste visible — la bloquer punirait la famille d'une panne de
 * configuration, et l'ecole ne la verrait jamais.
 */
enum StatutVerificationContact: string
{
    case EmailNonVerifie = 'email_non_verifie';
    case TelephoneNonVerifie = 'telephone_non_verifie';
    case Verifie = 'verifie';
    case Impossible = 'verification_impossible';

    /** @return list<string> */
    public static function valeursMasquees(): array
    {
        return [self::EmailNonVerifie->value, self::TelephoneNonVerifie->value];
    }
}
