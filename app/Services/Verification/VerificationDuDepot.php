<?php

namespace App\Services\Verification;

use App\Enums\StatutVerificationContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Apres un depot sur le portail : faut-il verifier le contact, et si oui,
 * lancer la verification.
 *
 * Seules les demandes NEUVES, ou celles dont la verification n'a pas abouti,
 * sont concernees. Une demande d'avant la verification qu'on redepose reste
 * visible comme avant : la masquer d'un coup ferait disparaitre de la
 * corbeille un dossier que l'ecole traitait peut-etre deja.
 *
 * Ne leve jamais : une panne ici ne doit ni faire echouer le depot, ni
 * laisser la demande masquee.
 */
class VerificationDuDepot
{
    public function __construct(private readonly DemarrageVerification $demarrage) {}

    public function apres(Model $demande): ?VerificationDemarree
    {
        // Un redepot qui change l'adresse ou le numero d'une demande deja
        // verifiee : le contact affiche a l'ecole n'est plus prouve.
        $contactChange = ! $demande->wasRecentlyCreated && ($demande->wasChanged('email') || $demande->wasChanged('telephone'));

        if (! $demande->wasRecentlyCreated && ! $demande->contactNonVerifie() && ! $contactChange) {
            return null;
        }

        if ($contactChange) {
            $demande->forceFill(['email_verifie_at' => null, 'telephone_verifie_at' => null])->saveQuietly();
        }

        try {
            return $this->demarrage->demarrer($demande);
        } catch (\Throwable $e) {
            Log::error('Verification de contact : demarrage interrompu, demande laissee visible', [
                'type' => $demande->typeDemandePublique(),
                'id' => $demande->getKey(),
                'erreur' => $e->getMessage(),
            ]);
            $demande->forceFill(['verification_contact' => StatutVerificationContact::Impossible->value])->saveQuietly();

            return null;
        }
    }
}
