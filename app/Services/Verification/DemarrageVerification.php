<?php

namespace App\Services\Verification;

use App\Enums\CanalVerification;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPVerificationContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Lance la verification du contact d'une demande publique.
 *
 * Regle cardinale : on ne MASQUE jamais une demande que l'ecole voyait deja.
 * Seules une demande NEUVE, ou une demande deja masquee, peuvent l'etre ; et
 * seulement APRES que le code est bien parti. Sans cela, quiconque connait le
 * numero d'une famille ferait disparaitre sa candidature en redeposant le
 * formulaire.
 *
 * - `apresDepot()` : depuis le portail. Neuve → verification masquante.
 *   Deja masquee → renvoi soumis au debit (un par minute, cinq par heure).
 *   Visible (ancienne ou verifiee) dont l'adresse ou le numero a change →
 *   les dates de verification tombent et un code part, SANS masquer.
 * - `demarrer(masquer: false)` : familles deja en base (commande dediee).
 *
 * Si le premier code ne part pas, la demande reste visible, en
 * « verification impossible ».
 */
class DemarrageVerification
{
    public function __construct(
        private readonly ContactDeVerification $contacts,
        private readonly ExpediteurVerification $expediteur,
        private readonly RenvoiVerification $renvoi,
    ) {}

    /** Ne leve jamais : une panne ici ne doit ni faire echouer le depot, ni masquer la demande. */
    public function apresDepot(Model $demande): ?VerificationDemarree
    {
        $masquable = $demande->wasRecentlyCreated || $demande->contactNonVerifie();
        $contactChange = ! $demande->wasRecentlyCreated && ($demande->wasChanged('email') || $demande->wasChanged('telephone'));

        if (! $masquable && ! $contactChange) {
            return null;
        }

        try {
            if (! $masquable) {
                $demande->poserVerificationContact(null, ['email_verifie_at' => null, 'telephone_verifie_at' => null]);
                $this->demarrer($demande, false);

                return null;
            }

            return $this->demarrer($demande, true);
        } catch (\Throwable $e) {
            Log::error('Verification de contact : demarrage interrompu, demande laissee visible', [
                'type' => $demande->typeDemandePublique(),
                'id' => $demande->getKey(),
                'erreur' => $e->getMessage(),
            ]);
            if ($masquable) {
                $demande->poserVerificationContact(StatutVerificationContact::Impossible);
            }

            return null;
        }
    }

    public function demarrer(Model $demande, bool $masquer = true): ?VerificationDemarree
    {
        $contact = $this->contacts->pour($demande);
        if ($contact === null) {
            $this->poser($demande, $masquer, StatutVerificationContact::Impossible);

            return null;
        }

        /** @var CanalVerification $canal */
        $canal = $contact['canal'];
        $existante = $this->existante($demande);

        if ($existante !== null && ! $existante->estVerifiee() && $existante->canal === $canal && $existante->destination === $contact['destination']) {
            $this->renvoi->renvoyer($existante->demande_id, $canal->value);

            return new VerificationDemarree($existante->demande_id, $canal, $contact['destination']);
        }

        $verification = $this->nouvelle($existante, $demande, $canal, $contact['destination'], $masquer);
        $envoi = $this->expediteur->expedier($verification);

        if (! $envoi->ok) {
            $this->poser($demande, $masquer, StatutVerificationContact::Impossible);
            Log::warning('Verification de contact : premier code non parti, demande laissee visible', [
                'demande_id' => $verification->demande_id,
                'type' => $demande->typeDemandePublique(),
                'code' => $envoi->code,
            ]);

            return null;
        }

        // Masquee seulement maintenant : la famille a de quoi confirmer.
        $this->poser($demande, $masquer, $canal->statutEnAttente());

        return new VerificationDemarree($verification->demande_id, $canal, $contact['destination']);
    }

    private function existante(Model $demande): ?ESBTPVerificationContact
    {
        return ESBTPVerificationContact::query()
            ->where('verifiable_type', $demande->getMorphClass())
            ->where('verifiable_id', $demande->getKey())
            ->first();
    }

    private function nouvelle(?ESBTPVerificationContact $ligne, Model $demande, CanalVerification $canal, string $destination, bool $masquer): ESBTPVerificationContact
    {
        $ligne ??= new ESBTPVerificationContact([
            'verifiable_type' => $demande->getMorphClass(),
            'verifiable_id' => $demande->getKey(),
        ]);

        $ligne->forceFill([
            'demande_id' => $ligne->demande_id ?: (string) Str::uuid(),
            'canal' => $canal,
            'destination' => $destination,
            'verifie_at' => null,
            'tentatives' => 0,
            'mailpulse_verification_id' => null,
            'masque_la_demande' => $masquer,
        ])->save();

        return $ligne;
    }

    private function poser(Model $demande, bool $masquer, StatutVerificationContact $statut): void
    {
        if ($masquer) {
            $demande->poserVerificationContact($statut);
        }
    }
}
