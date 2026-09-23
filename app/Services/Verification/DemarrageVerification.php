<?php

namespace App\Services\Verification;

use App\Enums\CanalVerification;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPVerificationContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pose la verification d'une demande publique et fait partir le premier code.
 *
 * Deux modes :
 * - `masquer` (depot du portail) : la demande reste invisible pour l'ecole
 *   jusqu'a la verification ;
 * - sans masquer (familles deja en base, commande dediee) : la demande reste
 *   visible, la verification ne fait que dater le contact.
 *
 * Si le PREMIER code ne part pas, quelle qu'en soit la raison, la demande
 * n'est jamais laissee masquee : elle passe en « verification impossible »,
 * visible, et le depot repond comme avant. Masquer une demande dont la famille
 * n'a recu aucun code la ferait disparaitre pour tout le monde.
 *
 * Un nouveau depot sur le MEME contact ne renvoie pas un code d'office : il
 * passe par le debit du renvoi (un par minute, cinq par heure), sans quoi
 * redeposer le formulaire en boucle inonderait la famille de messages.
 */
class DemarrageVerification
{
    public function __construct(
        private readonly ContactDeVerification $contacts,
        private readonly ExpediteurVerification $expediteur,
        private readonly RenvoiVerification $renvoi,
    ) {}

    public function demarrer(Model $demande, bool $masquer = true): ?VerificationDemarree
    {
        $contact = $this->contacts->pour($demande);
        if ($contact === null) {
            $this->poserStatut($demande, $masquer, StatutVerificationContact::Impossible);

            return null;
        }

        /** @var CanalVerification $canal */
        $canal = $contact['canal'];
        $existante = $this->existante($demande);

        if ($existante !== null && ! $existante->estVerifiee() && $existante->canal === $canal && $existante->destination === $contact['destination']) {
            $this->poserStatut($demande, $masquer, $canal->statutEnAttente());
            $this->renvoi->renvoyer($existante->demande_id, $canal->value);

            return new VerificationDemarree($existante->demande_id, $canal, $contact['destination']);
        }

        $verification = $this->nouvelle($existante, $demande, $canal, $contact['destination'], $masquer);
        $this->poserStatut($demande, $masquer, $canal->statutEnAttente());

        $envoi = $this->expediteur->expedier($verification);
        if (! $envoi->ok) {
            $this->poserStatut($demande, $masquer, StatutVerificationContact::Impossible);
            Log::warning('Verification de contact : premier code non parti, demande laissee visible', [
                'demande_id' => $verification->demande_id,
                'type' => $demande->typeDemandePublique(),
                'code' => $envoi->code,
            ]);

            return null;
        }

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

    private function poserStatut(Model $demande, bool $masquer, StatutVerificationContact $statut): void
    {
        if ($masquer) {
            $demande->forceFill(['verification_contact' => $statut->value])->saveQuietly();
        }
    }
}
