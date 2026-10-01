<?php

namespace App\Mail\Transport;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\Mailer\Exception\TransportException;

/**
 * MailPulse refuse en 429 au-delà de 60 courriels par minute et par
 * organisation (`API_RATE_LIMITS.EMAIL`, mailpulse `api-rate-limits.ts`).
 * Une rafale de relances perdait donc tout ce qui dépassait la minute.
 *
 * Cette cadence se tient AVANT l'appel, par instance : sous le plafond
 * réglé (`services.mailpulse.mail_per_minute`, 50 par défaut pour laisser
 * de la marge), le courriel part ; au-delà, deux conduites :
 *
 * - en console (worker de file, planificateur, commande) : on attend que la
 *   minute se libère, la rafale s'étale d'elle-même ;
 * - dans une requête web : on n'attend pas (le temps d'exécution est borné),
 *   on refuse à découvert. L'appelant le voit comme un échec d'envoi.
 *
 * Elle ne voit que SON instance : si plusieurs écoles partagent une
 * organisation MailPulse, leur somme peut encore dépasser 60. D'où le
 * second garde, `apresRefus()`, qui réessaie une fois un 429 en console.
 *
 * Le compteur vit dans le cache de l'application (`RateLimiter`) : pas de
 * tags, donc compatible avec le pilote `file` des instances.
 */
final class CadenceMailPulse
{
    private Closure $pause;

    public function __construct(?Closure $pause = null, private ?bool $peutAttendre = null)
    {
        $this->pause = $pause ?? static fn (int $secondes) => sleep($secondes);
    }

    public function avantEnvoi(array $contexte): void
    {
        $cle = 'mailpulse-courriels:'.config('app.tenant_code', '');
        $plafond = max(1, (int) config('services.mailpulse.mail_per_minute', 50));

        while (RateLimiter::tooManyAttempts($cle, $plafond)) {
            $attente = max(1, RateLimiter::availableIn($cle));

            if (! $this->attenteAutorisee()) {
                Log::warning('Courriel par MailPulse : plafond par minute atteint, envoi refusé', $contexte + ['plafond' => $plafond]);

                throw new TransportException("Plafond d'envoi par minute atteint ({$plafond}) : le courriel n'est pas parti, réessayez dans {$attente} s.");
            }

            Log::info('Courriel par MailPulse : envoi différé pour tenir le plafond par minute', $contexte + ['attente' => $attente]);
            ($this->pause)($attente);
        }

        RateLimiter::hit($cle, 60);
    }

    /** Après un 429 de MailPulse : en console, attendre une minute et dire « réessayer » une fois. */
    public function apresRefus(int $tentative): bool
    {
        if ($tentative > 1 || ! $this->attenteAutorisee()) {
            return false;
        }

        ($this->pause)(60);

        return true;
    }

    private function attenteAutorisee(): bool
    {
        return $this->peutAttendre ?? app()->runningInConsole();
    }
}
