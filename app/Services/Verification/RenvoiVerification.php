<?php

namespace App\Services\Verification;

use App\Models\ESBTPVerificationContact;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Renvoie un code : un par minute, cinq par heure, par demande.
 *
 * Le debit se compte sur l'identifiant RECU, qu'il existe ou non : une
 * demande inconnue coute le meme jeton et rend la meme reponse qu'une
 * demande reelle. Rien ne permet de savoir si un identifiant existe.
 */
class RenvoiVerification
{
    public function __construct(private readonly ExpediteurVerification $expediteur) {}

    /** @return int|null le delai d attente en secondes (debit local ou de MailPulse), sinon null */
    public function renvoyer(string $demandeId, ?string $canal): ?int
    {
        $empreinte = hash('sha256', $demandeId);
        $minute = 'verif-renvoi-min:'.$empreinte;
        $heure = 'verif-renvoi-h:'.$empreinte;

        foreach ([[$minute, 1], [$heure, (int) config('verification_contact.renvoi_max_par_heure', 5)]] as [$cle, $max]) {
            if (RateLimiter::tooManyAttempts($cle, $max)) {
                return max(1, RateLimiter::availableIn($cle));
            }
        }

        RateLimiter::hit($minute, (int) config('verification_contact.renvoi_intervalle_secondes', 60));
        RateLimiter::hit($heure, 3600);

        $verification = ESBTPVerificationContact::query()->where('demande_id', $demandeId)->first();
        if ($verification === null || $verification->estVerifiee() || ($canal !== null && $canal !== $verification->canal->value)) {
            return null;
        }

        // MailPulse limite aussi (par numero) : son delai est rendu tel quel au site.
        $envoi = $this->expediteur->expedier($verification);

        return $envoi->code === 'rate_limited' ? max(1, $envoi->retryAfter ?? 60) : null;
    }
}
