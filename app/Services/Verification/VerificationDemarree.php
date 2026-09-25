<?php

namespace App\Services\Verification;

use App\Enums\CanalVerification;

/** Ce que le depot annonce au site vitrine quand un code est parti. */
final class VerificationDemarree
{
    public function __construct(
        public readonly string $demandeId,
        public readonly CanalVerification $canal,
        private readonly string $destination,
    ) {}

    /** @return array{statut: string, demande_id: string, email_masque?: string, telephone_masque?: string} */
    public function reponse(): array
    {
        return [
            'statut' => $this->canal->statutPublic(),
            'demande_id' => $this->demandeId,
            $this->canal->cleMasque() => $this->canal === CanalVerification::Email
                ? MasqueContact::email($this->destination)
                : MasqueContact::telephone($this->destination),
        ];
    }
}
