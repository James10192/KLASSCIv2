<?php

namespace App\Services\Verification;

use App\Enums\StatutVerificationContact;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Une demande ne reste pas masquee pour toujours.
 *
 * Passe le delai (48 h par defaut) sans que la famille confirme, elle
 * redevient visible avec l'etat « verification expiree » : l'ecole la voit,
 * avec un badge « Contact non confirme », et decide. Une demande masquee a
 * jamais serait une famille perdue sans que personne le sache. Une
 * confirmation tardive reste acceptee.
 */
class ExpirationVerifications
{
    /** @return int le nombre de demandes rendues visibles */
    public function expirer(): int
    {
        $limite = now()->subHours((int) config('verification_contact.expiration_masquage_heures', 48));
        $n = 0;

        foreach ([ESBTPCandidature::class, ESBTPReinscriptionDemande::class] as $modele) {
            $exemple = new $modele;
            $modele::sansFiltreVerification()
                ->whereIn('verification_contact', StatutVerificationContact::valeursMasquees())
                ->whereExists(fn ($q) => $q->selectRaw('1')->from('esbtp_verifications_contact as v')
                    ->whereColumn('v.verifiable_id', $exemple->qualifyColumn('id'))
                    ->where('v.verifiable_type', $exemple->getMorphClass())
                    ->whereNull('v.verifie_at')
                    ->where('v.created_at', '<=', $limite))
                ->orderBy('id')
                ->each(function (Model $demande) use (&$n) {
                    $demande->poserVerificationContact(StatutVerificationContact::Expiree);
                    $n++;
                });
        }

        if ($n > 0) {
            Log::info('Verifications de contact expirees : demandes rendues visibles', ['nombre' => $n]);
        }

        return $n;
    }
}
