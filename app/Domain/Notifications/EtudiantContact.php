<?php

namespace App\Domain\Notifications;

/**
 * Contexte de contact d'un étudiant pour notifications/relances. Découple les
 * channels du modèle Eloquent — testable en pur PHP.
 */
final class EtudiantContact
{
    public function __construct(
        public readonly int $etudiantId,
        public readonly string $nomComplet,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $prenoms = null,
        public readonly ?string $nom = null,
    ) {}

    public function hasValidPhone(): bool
    {
        return PhoneNormalizer::isValid($this->phone);
    }

    public function hasEmail(): bool
    {
        return !empty($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
