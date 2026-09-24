<?php

namespace App\Enums;

/**
 * Ou en est la verification du contact d'une demande publique (candidature,
 * reinscription). Colonne `verification_contact`.
 *
 * NULL : pas de verification (reglage desactive, ou demande d'avant).
 *
 * La demande reste TOUJOURS visible par l'ecole. Tant que son contact n'est
 * pas prouve, elle porte un badge, apparait sous le filtre « Contact non
 * verifie », et n'est ni placee automatiquement en rendez-vous ni convoquee
 * par courriel : l'ecole appelle, puis « Confirmer le contact ».
 */
enum StatutVerificationContact: string
{
    /** Code parti par e-mail, pas encore saisi. */
    case EmailNonVerifie = 'email_non_verifie';

    /** Code parti par WhatsApp, pas encore saisi. */
    case TelephoneNonVerifie = 'telephone_non_verifie';

    case Verifie = 'verifie';

    /**
     * Le code n'a pas pu partir pour une raison qui ne tient pas a la famille
     * (MailPulse desactive, cle absente, WhatsApp indisponible), ou il n'y a
     * aucun contact joignable.
     */
    case Impossible = 'verification_impossible';

    /**
     * Un redepot a change l'adresse ou le numero d'une demande deja verifiee :
     * un code est parti, et l'ecole sait que le contact affiche n'est pas
     * encore prouve.
     */
    case AReconfirmer = 'contact_a_reconfirmer';

    /** @return list<string> Les etats ou un code attend la famille. */
    public static function valeursEnAttente(): array
    {
        return [self::EmailNonVerifie->value, self::TelephoneNonVerifie->value];
    }

    /**
     * Contact non prouve : ni placement automatique en rendez-vous, ni
     * convocation par courriel, tant que l'ecole ne l'a pas confirme.
     *
     * @return list<string>
     */
    public static function valeursAConfirmer(): array
    {
        return [
            self::EmailNonVerifie->value, self::TelephoneNonVerifie->value,
            self::Impossible->value, self::AReconfirmer->value,
        ];
    }

    /** Le badge a afficher a l'ecole, ou null. */
    public static function badge(?string $valeur): ?string
    {
        return match ($valeur) {
            self::EmailNonVerifie->value, self::TelephoneNonVerifie->value => 'Contact non vérifié',
            self::Impossible->value => 'Contact non vérifiable',
            self::AReconfirmer->value => 'Contact à reconfirmer',
            default => null,
        };
    }
}
