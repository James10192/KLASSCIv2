<?php

namespace App\Services\Verification;

use App\Enums\CanalVerification;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPVerificationContact;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Le contact est prouve : on le date, et la demande entre dans le circuit
 * normal de l'ecole (corbeille, compteurs, affectation des rendez-vous).
 *
 * Une verification lancee sur une demande deja visible (familles en base) ne
 * touche pas `verification_contact` : elle ne fait que dater le contact.
 */
class FinalisationVerification
{
    public function valider(ESBTPVerificationContact $verification): ResultatControle
    {
        $maintenant = now();
        $verification->forceFill(['verifie_at' => $maintenant, 'code_hash' => null, 'jeton_hash' => null])->save();

        $demande = $verification->verifiable;
        if ($demande === null) {
            return ResultatControle::refus(ControleVerification::CODE_INVALIDE);
        }

        $champs = [
            $verification->canal === CanalVerification::Email ? 'email_verifie_at' : 'telephone_verifie_at' => $maintenant,
        ];
        if ($verification->masque_la_demande || $demande->contactNonVerifie()) {
            $champs['verification_contact'] = StatutVerificationContact::Verifie->value;
        }
        $demande->forceFill($champs)->save();

        if ($demande instanceof ESBTPReinscriptionDemande) {
            // Le badge de la barre laterale compte les demandes en attente :
            // celle-ci vient d'y entrer.
            Cache::forget(ESBTPReinscriptionDemande::CLE_CACHE_EN_ATTENTE);
        }

        Log::info('Verification de contact aboutie', [
            'demande_id' => $verification->demande_id,
            'type' => $demande->typeDemandePublique(),
            'canal' => $verification->canal->value,
        ]);

        return ResultatControle::verifiee($demande->typeDemandePublique());
    }
}
