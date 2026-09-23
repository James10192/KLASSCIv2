<?php

namespace App\Services\Verification;

use App\Enums\CanalVerification;
use App\Models\ESBTPVerificationContact;
use App\Services\MailPulse\MailPulseVerifications;
use App\Services\MailPulse\ResultatVerificationDistante;
use App\Services\RendezVous\MessagerieRdv;
use Illuminate\Support\Facades\Log;

/**
 * Fait partir un NOUVEAU code (et, pour l'e-mail, un nouveau lien) et
 * consigne l'envoi sur la ligne. Tout code precedent cesse de valoir.
 *
 * Ne journalise jamais ni le code, ni le jeton, ni la destination.
 */
class ExpediteurVerification
{
    public function __construct(
        private readonly CourrielVerification $courriel,
        private readonly MailPulseVerifications $whatsapp,
    ) {}

    public function expedier(ESBTPVerificationContact $verification): ResultatVerificationDistante
    {
        $resultat = $verification->canal === CanalVerification::Email
            ? $this->parEmail($verification)
            : $this->parWhatsapp($verification);

        $verification->forceFill([
            'dernier_envoi_at' => now(),
            'dernier_echec' => $resultat->ok ? null : mb_substr($resultat->code, 0, 60),
        ])->save();

        if (! $resultat->ok) {
            Log::warning('Verification de contact : envoi refuse', [
                'demande_id' => $verification->demande_id,
                'canal' => $verification->canal->value,
                'code' => $resultat->code,
                'definitif' => $resultat->definitif(),
            ]);
        }

        return $resultat;
    }

    private function parEmail(ESBTPVerificationContact $verification): ResultatVerificationDistante
    {
        $code = SecretsVerification::nouveauCode();
        $jeton = SecretsVerification::nouveauJeton();

        $verification->forceFill([
            'code_hash' => SecretsVerification::empreinte($code),
            'jeton_hash' => SecretsVerification::empreinte($jeton),
            'code_expire_at' => now()->addMinutes((int) config('verification_contact.code_expire_minutes', 30)),
            'jeton_expire_at' => now()->addHours((int) config('verification_contact.lien_expire_heures', 48)),
            'tentatives' => 0,
        ])->save();

        $envoi = $this->courriel->expedier($verification->destination, $code, $jeton);

        if ($envoi->ok) {
            $verification->forceFill(['mailpulse_message_id' => $envoi->id ? mb_substr($envoi->id, 0, 100) : null])->save();

            return ResultatVerificationDistante::ok('sent', (string) $envoi->id);
        }

        return in_array($envoi->status, MessagerieRdv::REFUS_DE_CONFIGURATION, true)
            ? ResultatVerificationDistante::echec('verification_indisponible', $envoi->httpStatus)
            : ResultatVerificationDistante::echec($envoi->errorCode ?: $envoi->status, $envoi->httpStatus);
    }

    private function parWhatsapp(ESBTPVerificationContact $verification): ResultatVerificationDistante
    {
        $resultat = $this->whatsapp->creer($verification->destination, $verification->demande_id);

        $verification->forceFill([
            'mailpulse_verification_id' => $resultat->ok ? mb_substr((string) $resultat->id, 0, 100) : $verification->mailpulse_verification_id,
            'tentatives' => $resultat->ok ? 0 : $verification->tentatives,
            'code_expire_at' => $resultat->ok ? now()->addMinutes((int) config('verification_contact.code_expire_minutes', 30)) : $verification->code_expire_at,
        ])->save();

        return $resultat;
    }
}
