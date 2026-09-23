<?php

namespace App\Services\MailPulse;

use Illuminate\Http\Client\Response;

/**
 * L'API de verification WhatsApp de MailPulse (`/api/v1/verifications`).
 *
 * MailPulse genere, envoie et controle le code : KLASSCI ne le voit jamais.
 * Chaque appel rend un ResultatVerificationDistante dont le `code` est l'un
 * de ceux du contrat MailPulse, ou un code local (`disabled`,
 * `missing_api_key`, `connection_failed`).
 */
class MailPulseVerifications
{
    public function __construct(private readonly MailPulseApi $api) {}

    public function creer(string $telephoneE164, string $reference): ResultatVerificationDistante
    {
        $reponse = $this->api->appeler('POST', '/api/v1/verifications', [
            'channel' => 'whatsapp',
            'to' => $telephoneE164,
            'locale' => 'fr',
            'reference' => $reference,
        ], 'verification_create');

        if (is_string($reponse)) {
            return ResultatVerificationDistante::echec($reponse);
        }

        if ($reponse->status() === 201) {
            $id = $reponse->json('id');

            return is_string($id) && $id !== ''
                ? ResultatVerificationDistante::ok('pending', $id)
                : ResultatVerificationDistante::echec('invalid_contract');
        }

        return $this->echec($reponse);
    }

    public function controler(string $verificationId, string $code): ResultatVerificationDistante
    {
        $reponse = $this->api->appeler('POST', '/api/v1/verifications/'.rawurlencode($verificationId).'/check', ['code' => $code], 'verification_check');

        if (is_string($reponse)) {
            return ResultatVerificationDistante::echec($reponse);
        }

        if ($reponse->status() === 200 && $reponse->json('status') === 'approved') {
            return ResultatVerificationDistante::ok('approved', $verificationId);
        }

        return $this->echec($reponse);
    }

    private function echec(Response $reponse): ResultatVerificationDistante
    {
        $code = $reponse->json('error');
        $retry = $reponse->json('retry_after');

        return ResultatVerificationDistante::echec(
            is_string($code) && $code !== '' ? $code : match ($reponse->status()) {
                429 => 'rate_limited',
                401, 403 => 'auth_failed',
                404 => 'introuvable',
                default => 'provider_unavailable',
            },
            $reponse->status(),
            is_numeric($retry) ? (int) $retry : null,
        );
    }
}
