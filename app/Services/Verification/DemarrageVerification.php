<?php

namespace App\Services\Verification;

use App\Enums\CanalVerification;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPVerificationContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Lance la verification du contact d'une demande publique.
 *
 * Regle cardinale : on ne MASQUE jamais une demande que l'ecole voyait deja.
 * Seules une demande NEUVE, ou une demande deja masquee, peuvent l'etre ; et
 * seulement APRES que le code est parti (ou que MailPulse a demande d'attendre).
 *
 * - `apresDepot()` : depuis le portail. Neuve → verification masquante.
 *   Deja masquee → renvoi soumis au debit. Visible dont l'adresse ou le
 *   numero a change → un code part SANS masquer, la demande passe en
 *   « contact a reconfirmer » et la famille recoit son `demande_id`.
 * - `demarrer(masquer: false)` : familles deja en base (commande dediee).
 *
 * La ligne de verification se lit et se reinitialise sous verrou : deux depots
 * simultanes ne se marchent pas dessus, et une verification aboutie entre-temps
 * n'est jamais effacee.
 */
class DemarrageVerification
{
    /** Le code du dernier refus d'envoi, pour qui lance des lots (la commande s'arrete dessus). */
    public ?string $dernierRefus = null;

    public function __construct(
        private readonly ContactDeVerification $contacts,
        private readonly ExpediteurVerification $expediteur,
        private readonly RenvoiVerification $renvoi,
        private readonly FinalisationVerification $finalisation,
    ) {}

    /** Ne leve jamais : une panne ici ne doit ni faire echouer le depot, ni masquer la demande. */
    public function apresDepot(Model $demande): ?VerificationDemarree
    {
        $masquable = $demande->wasRecentlyCreated || $demande->contactNonVerifie();

        try {
            if ($masquable) {
                return $this->demarrer($demande, true);
            }
            if (! $this->contactChange($demande)) {
                // Visible mais jamais confirmee (expiree, impossible) : la famille
                // revient, on lui redonne un code, sans rien masquer.
                return $demande->contactAConfirmer() ? $this->demarrer($demande, false) : null;
            }

            $demande->poserVerificationContact(StatutVerificationContact::AReconfirmer, ['email_verifie_at' => null, 'telephone_verifie_at' => null]);

            return $this->demarrer($demande, false);
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
        $this->dernierRefus = null;
        $contact = $this->contacts->pour($demande);
        if ($contact === null) {
            $this->poser($demande, $masquer, StatutVerificationContact::Impossible);

            return null;
        }

        /** @var CanalVerification $canal */
        $canal = $contact['canal'];
        [$ligne, $etape, $nouvelle] = $this->preparer($demande, $canal, $contact['destination'], $masquer);

        if ($etape === 'verifiee') {
            return null;
        }
        if ($etape === 'en_cours') {
            $this->renvoi->renvoyer($ligne->demande_id, $canal->value);

            return new VerificationDemarree($ligne->demande_id, $canal, $contact['destination'], $masquer);
        }

        $envoi = $this->expediteur->expedier($ligne);

        // Un redepot concurrent a repris la ligne pendant l'envoi : c'est lui
        // qui decide desormais. On ne supprime ni ne marque rien.
        if ($envoi->code === ExpediteurVerification::CONTACT_CHANGE) {
            $this->dernierRefus = $envoi->code;

            return null;
        }

        // MailPulse demande d'attendre, au depot : ce n'est pas une impossibilite.
        // La famille redemandera un code ; l'expiration a 48 h reste le filet.
        // Hors depot (lot), tout envoi rate est abandonne sans laisser de ligne.
        if (! $envoi->ok && ($envoi->code !== 'rate_limited' || ! $masquer)) {
            $this->dernierRefus = $envoi->code;
            if ($nouvelle) {
                $ligne->delete();
            }
            $this->poser($demande, $masquer, StatutVerificationContact::Impossible);
            Log::warning('Verification de contact : premier code non parti, demande laissee visible', [
                'type' => $demande->typeDemandePublique(),
                'id' => $demande->getKey(),
                'code' => $envoi->code,
            ]);

            return null;
        }

        $this->dernierRefus = $envoi->ok ? null : $envoi->code;
        if ($masquer) {
            $ligne->forceFill(['masquee_at' => $ligne->masquee_at ?? now()])->save();
            $this->poser($demande, true, $canal->statutEnAttente());
        }

        return new VerificationDemarree($ligne->demande_id, $canal, $contact['destination'], $masquer);
    }

    /**
     * Sous verrou : relit la ligne et l'etat de la demande, puis decide.
     *
     * @return array{0: ESBTPVerificationContact, 1: 'verifiee'|'en_cours'|'a_envoyer', 2: bool}
     */
    private function preparer(Model $demande, CanalVerification $canal, string $destination, bool $masquer): array
    {
        return DB::transaction(function () use ($demande, $canal, $destination, $masquer) {
            $ligne = ESBTPVerificationContact::query()
                ->where('verifiable_type', $demande->getMorphClass())
                ->where('verifiable_id', $demande->getKey())
                ->lockForUpdate()
                ->first();
            $meme = $ligne !== null && $ligne->canal === $canal && $ligne->destination === $destination;

            if ($meme && $ligne->estVerifiee()) {
                // Deja prouve pour ce contact : on remet la demande d'aplomb, rien ne part.
                $this->finalisation->valider($ligne);

                return [$ligne, 'verifiee', false];
            }
            if ($meme && ! $this->aReinitialiser($ligne)) {
                return [$ligne, 'en_cours', false];
            }

            $nouvelle = $ligne === null;
            $ligne ??= new ESBTPVerificationContact(['verifiable_type' => $demande->getMorphClass(), 'verifiable_id' => $demande->getKey()]);
            $ligne->forceFill([
                'demande_id' => $ligne->demande_id ?: (string) Str::uuid(),
                'canal' => $canal,
                'destination' => $destination,
                'verifie_at' => null,
                'tentatives' => 0,
                'mailpulse_verification_id' => null,
                // Rien de l'ancien contact ne doit valoir pour le nouveau.
                'code_hash' => null,
                'jeton_hash' => null,
                'code_expire_at' => null,
                'jeton_expire_at' => null,
                'mailpulse_message_id' => null,
                'dernier_envoi_at' => null,
                'dernier_echec' => null,
                'masque_la_demande' => $masquer,
                'masquee_at' => $masquer ? $ligne->masquee_at : null,
            ])->save();

            return [$ligne, 'a_envoyer', $nouvelle];
        });
    }

    /** Une ligne dont aucun code n'est jamais parti se reprend depuis zero. */
    private function aReinitialiser(ESBTPVerificationContact $ligne): bool
    {
        return $ligne->dernier_envoi_at === null;
    }

    /** Le contact du depot differe-t-il de ce que l'ecole avait (redepot, ou ligne existante) ? */
    private function contactChange(Model $demande): bool
    {
        if ($demande->contactModifieAuDepot) {
            return true;
        }

        $contact = $this->contacts->pour($demande);
        $ligne = ESBTPVerificationContact::query()
            ->where('verifiable_type', $demande->getMorphClass())
            ->where('verifiable_id', $demande->getKey())
            ->first();

        return $ligne !== null && $contact !== null
            && ($ligne->canal !== $contact['canal'] || $ligne->destination !== $contact['destination']);
    }

    private function poser(Model $demande, bool $masquer, StatutVerificationContact $statut): void
    {
        if ($masquer) {
            $demande->poserVerificationContact($statut);
        }
    }
}
