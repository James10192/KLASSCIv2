<?php

namespace App\Services\RendezVous\Rattrapage;

use App\Services\Portail\ReferencePublique;
use Carbon\CarbonImmutable;

/**
 * Un courriel de convocation tel que l'extraction cote MailPulse le decrit.
 * La reference est normalisee (majuscules, sans tirets) pour se comparer a
 * `reference_publique` ; le destinataire n'arrive jamais en clair, seulement
 * son empreinte sha256 (adresse en minuscules, sans espaces) et son domaine.
 */
final class MessageConvocation
{
    public function __construct(
        public readonly string $reference,
        public readonly string $messageId,
        public readonly CarbonImmutable $envoyeAt,
        public readonly ?string $destinataireSha256,
        public readonly string $destinataireDomaine,
        public readonly string $action,
    ) {}

    /** @param  array{reference: string, message_id: string, envoye_at: string, destinataire_sha256?: ?string, destinataire_domaine: string, action: string}  $brut */
    public static function depuis(array $brut, ReferencePublique $references): self
    {
        $empreinte = $brut['destinataire_sha256'] ?? null;

        return new self(
            $references->normaliser($brut['reference']),
            $brut['message_id'],
            CarbonImmutable::parse($brut['envoye_at']),
            $empreinte === null ? null : strtolower($empreinte),
            mb_strtolower(trim($brut['destinataire_domaine'])),
            $brut['action'],
        );
    }

    /** Meme calcul que l'extraction : sha256 de l'adresse en minuscules, sans espaces autour. */
    public static function empreinte(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }
}
