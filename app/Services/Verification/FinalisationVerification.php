<?php

namespace App\Services\Verification;

use App\Enums\CanalVerification;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPVerificationContact;
use Illuminate\Support\Facades\Log;

/**
 * Le contact est prouve : on le date, et la demande entre dans le circuit
 * normal de l'ecole (corbeille, compteurs, affectation des rendez-vous).
 *
 * Appelee sous le verrou de la ligne de verification. Une verification lancee
 * sur une demande deja visible (familles en base, contact change) ne touche
 * pas l'etat de visibilite : elle ne fait que dater le contact.
 *
 * Le code qui a verifie est garde en empreinte : une ligne verifiee ne repond
 * « verifie » qu'a ce code-la (WhatsApp compris, dont MailPulse detient le
 * code : on retient celui qu'il a approuve).
 */
class FinalisationVerification
{
    public function valider(ESBTPVerificationContact $verification, ?string $code = null): ResultatControle
    {
        $maintenant = $verification->verifie_at ?? now();
        $verification->forceFill([
            'verifie_at' => $maintenant,
            'code_hash' => $code !== null ? SecretsVerification::empreinte($code) : $verification->code_hash,
        ])->save();

        $demande = $verification->verifiable;
        if ($demande === null) {
            return ResultatControle::refus(ControleVerification::CODE_INVALIDE);
        }

        $date = [$verification->canal === CanalVerification::Email ? 'email_verifie_at' : 'telephone_verifie_at' => $maintenant];
        $rendreVisible = $verification->masque_la_demande || $demande->contactNonVerifie()
            || $demande->verification_contact === StatutVerificationContact::Expiree->value;
        $demande->poserVerificationContact($rendreVisible ? StatutVerificationContact::Verifie : null, $date);

        Log::info('Verification de contact aboutie', [
            'demande_id' => $verification->demande_id,
            'type' => $demande->typeDemandePublique(),
            'canal' => $verification->canal->value,
        ]);

        return ResultatControle::verifiee($demande->typeDemandePublique());
    }
}
