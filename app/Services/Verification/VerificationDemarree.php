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

    /**
     * `lien_whatsapp` n'apparait que pour une verification inversee : le site
     * affiche alors un bouton qui ouvre WhatsApp au lieu d'un champ de code.
     *
     * @return array{statut: string, demande_id: string, email_masque?: string, telephone_masque?: string, lien_whatsapp?: string}
     */
    public function reponse(): array
    {
        $reponse = [
            'statut' => $this->canal->statutPublic(),
            'demande_id' => $this->demandeId,
            $this->canal->cleMasque() => $this->canal === CanalVerification::Email
                ? MasqueContact::email($this->destination)
                : MasqueContact::telephone($this->destination),
        ];
        $lien = $this->canal === CanalVerification::Telephone ? LienWhatsappVerification::lire($this->demandeId) : null;

        return $lien === null ? $reponse : $reponse + ['lien_whatsapp' => $lien];
    }
}
