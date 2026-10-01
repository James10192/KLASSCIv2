<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * MailPulse refuse en 429 au-delà de 60 courriels par minute et par
 * organisation (`API_RATE_LIMITS.EMAIL`, mailpulse `api-rate-limits.ts`).
 *
 * Cette cadence compte, par instance, les courriels que le mailer remet à
 * MailPulse (`services.mailpulse.mail_per_minute`, 30 par défaut). Au-delà, elle
 * REFUSE (`DebitMailPulseAtteint`) sans appeler MailPulse. Elle n'attend jamais :
 * un `sleep()` dans un worker dépasse son délai (60 s) ou le `retry_after` de la
 * file (90 s), et le job repart en double ; dans `schedule:run`, il bloque les
 * autres tâches.
 *
 * Ce que devient le refus dépend de l'appelant :
 * - un job de file est REMIS en file avec le délai indiqué
 *   (`AppServiceProvider::relacherLesJobsRefusesParMailPulse()`) ;
 * - ailleurs (requête web, commande, planificateur), c'est un échec d'envoi
 *   ordinaire, journalisé, que l'appelant traite comme tel.
 *
 * C'est un garde au mieux, pas une garantie : la fenêtre est fixe ici et
 * glissante chez MailPulse, l'incrément du cache `file` n'est pas atomique, et le
 * compteur ignore les autres écoles d'une même organisation comme les autres
 * flux MailPulse. Le 429 de MailPulse reste possible ; il prend le même chemin.
 */
final class CadenceMailPulse
{
    public function avantEnvoi(array $contexte): void
    {
        $cle = 'mailpulse-courriels:'.config('app.tenant_code', '');
        $plafond = max(1, (int) config('services.mailpulse.mail_per_minute', 30));

        if (RateLimiter::tooManyAttempts($cle, $plafond)) {
            $attente = max(1, RateLimiter::availableIn($cle));
            Log::warning('Courriel par MailPulse : plafond par minute atteint, envoi refusé', $contexte + [
                'plafond' => $plafond,
                'reessayer_dans' => $attente,
            ]);

            throw new DebitMailPulseAtteint(
                "Plafond d'envoi par minute atteint ({$plafond}) : le courriel n'est pas parti, réessayez dans {$attente} s.",
                $attente
            );
        }

        RateLimiter::hit($cle, 60);
    }
}
