<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\RateLimiter;

/**
 * MailPulse refuse en 429 au-delà de 60 courriels par minute et par
 * organisation (`API_RATE_LIMITS.EMAIL`, mailpulse `api-rate-limits.ts`).
 *
 * Cette cadence compte, par instance, les courriels que le mailer remet à
 * MailPulse (`services.mailpulse.mail_per_minute`, 30 par défaut). Au-delà, elle
 * dit combien de secondes attendre, sans appeler MailPulse et sans jamais
 * dormir : un `sleep()` dans un worker dépasse son délai (60 s) ou le
 * `retry_after` de la file (90 s), et le job repart en double.
 *
 * Ce que devient un courriel retenu : `MailPulseTransport` le confie à
 * `RemettreCourrielMailPulse`, qui le remet plus tard.
 *
 * C'est un garde au mieux, pas une garantie : la fenêtre est fixe ici et
 * glissante chez MailPulse, l'incrément du cache `file` n'est pas atomique, et le
 * compteur ignore les autres écoles d'une même organisation comme les autres
 * flux MailPulse. Le 429 de MailPulse reste possible ; il prend le même chemin.
 */
final class CadenceMailPulse
{
    /** Null : la place est prise, le courriel peut partir. Sinon : secondes à attendre. */
    public function attenteAvantEnvoi(): ?int
    {
        $cle = 'mailpulse-courriels:'.config('app.tenant_code', '');
        $plafond = max(1, (int) config('services.mailpulse.mail_per_minute', 30));

        if (RateLimiter::tooManyAttempts($cle, $plafond)) {
            return max(1, RateLimiter::availableIn($cle));
        }

        RateLimiter::hit($cle, 60);

        return null;
    }
}
