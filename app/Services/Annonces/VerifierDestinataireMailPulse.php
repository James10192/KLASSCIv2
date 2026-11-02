<?php

namespace App\Services\Annonces;

use App\Services\MailPulse\MailPulseApi;

class VerifierDestinataireMailPulse
{
    public function __construct(private readonly MailPulseApi $api) {}

    /** Fail closed unless Resend explicitly says the address is NOT suppressed. */
    public function estAutorise(string $adresse, string $tenant): bool
    {
        if (! filter_var($adresse, FILTER_VALIDATE_EMAIL) || $tenant === '' || $tenant === 'default') {
            return false;
        }

        $chemin = '/api/v1/messages/recipient-eligibility?email='.rawurlencode($adresse)
            .'&tenant='.rawurlencode($tenant);
        $reponse = $this->api->appeler('GET', $chemin, null, 'announcement_retry_suppression', 10, 1);

        return ! is_string($reponse)
            && $reponse->successful()
            && $reponse->json('tenant') === $tenant
            && $reponse->json('email') === $adresse
            && $reponse->json('eligible') === true
            && $reponse->json('suppressed') === false;
    }
}
