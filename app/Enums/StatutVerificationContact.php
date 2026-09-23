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

    /**
     * Masquee plus de 48 h sans que la famille confirme : la demande redevient
     * visible, avec un badge, plutot que de disparaitre pour toujours.
     */
    case Expiree = 'verification_expiree';

    /**
     * Demande visible dont un redepot a change l'adresse ou le numero : un code
     * est parti, la demande reste visible, et l'ecole sait que le contact
     * affiche n'est pas encore prouve.
     */
    case AReconfirmer = 'contact_a_reconfirmer';

    /** @return list<string> */
    public static function valeursMasquees(): array
    {
        return [self::EmailNonVerifie->value, self::TelephoneNonVerifie->value];
    }

    /**
     * Visibles, mais sans contact prouve : ni placement automatique en
     * rendez-vous, ni convocation par courriel, tant que l'ecole n'a pas
     * confirme le contact elle-meme (« Confirmer le contact »).
     *
     * @return list<string>
     */
    public static function valeursAConfirmer(): array
    {
        return [self::Expiree->value, self::Impossible->value, self::AReconfirmer->value];
    }

    /** Le badge a afficher a l'ecole sur une demande visible, ou null. */
    public static function badge(?string $valeur): ?string
    {
        return match ($valeur) {
            self::Expiree->value => 'Contact non confirmé',
            self::Impossible->value => 'Contact non vérifiable',
            self::AReconfirmer->value => 'Contact à reconfirmer',
            default => null,
        };
    }
}
