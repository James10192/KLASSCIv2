<?php

namespace App\Enums;

/**
 * Par ou passe le code de verification d'une demande publique.
 *
 * `telephone` veut dire WhatsApp : c'est MailPulse qui genere, envoie et
 * controle le code. Pour l'e-mail, KLASSCI genere le code et le lien, et
 * MailPulse ne fait que transporter le courriel.
 */
enum CanalVerification: string
{
    case Email = 'email';
    case Telephone = 'telephone';

    public function statutEnAttente(): StatutVerificationContact
    {
        return $this === self::Email
            ? StatutVerificationContact::EmailNonVerifie
            : StatutVerificationContact::TelephoneNonVerifie;
    }

    /** La cle du masque dans la reponse de creation (`email_masque` / `telephone_masque`). */
    public function cleMasque(): string
    {
        return $this->value.'_masque';
    }

    /** Le `statut` annonce au site vitrine a la creation. */
    public function statutPublic(): string
    {
        return 'verification_'.$this->value.'_requise';
    }
}
