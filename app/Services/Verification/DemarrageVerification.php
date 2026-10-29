<?php

namespace App\Services\Verification;

use App\Enums\CanalVerification;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPVerificationContact;
use App\Services\TenantScolariteSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Lance la verification du contact d'une demande publique, si l'ecole l'a
 * activee (reglage `inscriptions.portail.verification_contact`, coupe par
 * defaut). Reglage coupe : rien ne part, rien n'est marque, le portail repond
 * comme avant.
 *
 * La demande n'est JAMAIS masquee. Elle est MARQUEE (code en attente,
 * impossible, a reconfirmer) : badge, filtre « Contact non verifie », et ni
 * placement automatique en rendez-vous ni convocation par courriel tant que
 * le contact n'est pas prouve ou confirme par l'ecole.
 *
 * - `apresDepot()` : depuis le portail. Neuve, ou code deja en attente →
 *   verification marquante (un renvoi passe par le debit du renvoi). Deja
 *   traitee dont l'adresse ou le numero a change → « contact a reconfirmer »
 *   et un nouveau code. Marquee impossible → un nouveau code.
 * - `demarrer(marquer: false)` : familles deja en base (commande dediee) : la
 *   verification ne fait que dater le contact.
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
        private readonly TenantScolariteSettings $reglages,
    ) {}

    public function active(): bool
    {
        return $this->reglages->verificationContactActive();
    }

    /** Ne leve jamais : une panne ici ne doit pas faire echouer le depot. */
    public function apresDepot(Model $demande): ?VerificationDemarree
    {
        if (! $this->active()) {
            return null;
        }

        try {
            if ($demande->wasRecentlyCreated || $demande->contactNonVerifie()) {
                return $this->demarrer($demande, true);
            }
            if ($this->contactChange($demande)) {
                // Le badge « a reconfirmer » reste tant que la famille n'a pas saisi le code.
                $demande->poserVerificationContact(StatutVerificationContact::AReconfirmer, ['email_verifie_at' => null, 'telephone_verifie_at' => null]);

                return $this->demarrer($demande, false);
            }

            // Marquee sans code en cours (impossible, a reconfirmer) : la famille
            // revient, on lui redonne un code.
            if (! $demande->contactAConfirmer()) {
                return null;
            }

            return $this->demarrer($demande, $demande->verification_contact !== StatutVerificationContact::AReconfirmer->value);
        } catch (\Throwable $e) {
            Log::error('Verification de contact : demarrage interrompu', [
                'type' => $demande->typeDemandePublique(),
                'id' => $demande->getKey(),
                'erreur' => $e->getMessage(),
            ]);

            return null;
        }
    }
    public function demarrer(Model $demande, bool $marquer = true): ?VerificationDemarree
    {
        $this->dernierRefus = null;
        $contact = $this->contacts->pour($demande);
        if ($contact === null) {
            $this->poser($demande, $marquer, StatutVerificationContact::Impossible);

            return null;
        }

        /** @var CanalVerification $canal */
        $canal = $contact['canal'];
        [$ligne, $etape, $nouvelle] = $this->preparer($demande, $canal, $contact['destination']);

        if ($etape === 'verifiee') {
            return null;
        }
        if ($etape === 'en_cours') {
            $this->renvoi->renvoyer($ligne->demande_id, $canal->value);

            return new VerificationDemarree($ligne->demande_id, $canal, $contact['destination']);
        }

        $envoi = $this->expediteur->expedier($ligne);

        // Un redepot concurrent a repris la ligne pendant l'envoi : c'est lui
        // qui decide desormais. On ne supprime ni ne marque rien.
        if ($envoi->code === ExpediteurVerification::CONTACT_CHANGE) {
            $this->dernierRefus = $envoi->code;

            return null;
        }

        // MailPulse demande d'attendre, au depot : ce n'est pas une impossibilite,
        // la famille redemandera un code. Hors depot (lot), tout envoi rate est
        // abandonne sans laisser de ligne.
        if (! $envoi->ok && ($envoi->code !== 'rate_limited' || ! $marquer)) {
            $this->dernierRefus = $envoi->code;
            if ($nouvelle) {
                $ligne->delete();
            }
            $this->poser($demande, $marquer, StatutVerificationContact::Impossible);
            Log::warning('Verification de contact : premier code non parti', [
                'type' => $demande->typeDemandePublique(),
                'id' => $demande->getKey(),
                'code' => $envoi->code,
            ]);

            return null;
        }

        $this->dernierRefus = $envoi->ok ? null : $envoi->code;
        $this->poser($demande, $marquer, $canal->statutEnAttente());

        return new VerificationDemarree($ligne->demande_id, $canal, $contact['destination']);
    }

    /**
     * Sous verrou : relit la ligne et l'etat de la demande, puis decide.
     *
     * @return array{0: ESBTPVerificationContact, 1: 'verifiee'|'en_cours'|'a_envoyer', 2: bool}
     */
    private function preparer(Model $demande, CanalVerification $canal, string $destination): array
    {
        return DB::transaction(function () use ($demande, $canal, $destination) {
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

    private function poser(Model $demande, bool $marquer, StatutVerificationContact $statut): void
    {
        if ($marquer) {
            $demande->poserVerificationContact($statut);
        }
    }
}
