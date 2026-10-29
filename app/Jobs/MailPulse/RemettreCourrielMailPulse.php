<?php

namespace App\Jobs\MailPulse;

use App\Mail\Transport\EnvoiMailPulse;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Remet plus tard UN courriel que MailPulse ne pouvait pas prendre tout de suite
 * (plafond de débit local, 429 de débit, MailPulse indisponible ou injoignable). `MailPulseTransport` le crée au
 * lieu de lever : l'appelant — une requête web, une boucle de relances, un appel
 * de fin de cours — n'a rien à rattraper, et le courriel n'est pas perdu.
 *
 * Il porte la charge MailPulse déjà construite et sa clé d'idempotence : la
 * reprise envoie exactement le même message, et MailPulse ne le doublerait pas.
 *
 * Patience bornée dans le temps, pas en essais : `retryUntil()` (deux heures,
 * figé à la mise en file) fait ignorer `--tries` par le worker. Chaque refus de
 * débit le relâche avec au moins `DELAI_MINIMAL` secondes, plus un peu de hasard
 * pour que les courriels retenus ne retombent pas tous dans la même minute — soit
 * au plus 120 tentatives, sous le plafond de `jobs.attempts` (255).
 *
 * Un refus définitif (adresse refusée, clé invalide, quota du mois) le fait
 * échouer tout de suite, journalisé : il ne passerait pas davantage dans deux
 * heures. Rejouer un refus passager, lui, est sans risque : la clé d'idempotence
 * fait renvoyer par MailPulse le message déjà pris au lieu d'en créer un second.
 *
 * Chiffré en file (`ShouldBeEncrypted`) : la charge porte le corps du courriel,
 * donc parfois un mot de passe initial ou un lien de réinitialisation, et
 * `jobs` comme `failed_jobs` partent dans les sauvegardes.
 */
final class RemettreCourrielMailPulse implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const PATIENCE_HEURES = 2;

    public const DELAI_MINIMAL = 60;

    public function __construct(
        public array $charge,
        public string $cleIdempotence,
        public array $contexte = [],
    ) {
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(self::PATIENCE_HEURES);
    }

    public function handle(EnvoiMailPulse $envoi): void
    {
        try {
            $attente = $envoi->tenter($this->charge, $this->cleIdempotence, $this->contexte);
        } catch (Throwable $e) {
            $this->fail($e);

            return;
        }

        if ($attente === null) {
            Log::info('Courriel différé parti par MailPulse', $this->contexte + ['essai' => $this->attempts()]);

            return;
        }

        $this->release(max(self::DELAI_MINIMAL, $attente) + random_int(0, 30));
    }

    public function failed(Throwable $exception): void
    {
        // Pas le message brut : celui de MailPulse peut citer l'adresse. Le statut
        // et le code du refus sont sur la ligne « Courriel refusé par MailPulse ».
        Log::error('Courriel différé abandonné', $this->contexte + [
            'domaine_destinataire' => substr(strrchr((string) ($this->charge['recipient']['value'] ?? ''), '@') ?: '', 1),
            'cause' => class_basename($exception),
            'http' => $exception->getCode() ?: null,
        ]);
    }
}
