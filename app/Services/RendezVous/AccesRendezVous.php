<?php

namespace App\Services\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Enums\CanalVerification;
use App\Services\MailPulse\MailPulseVerifications;
use App\Services\Portail\ReferencePublique;
use App\Services\TenantScolariteSettings;
use App\Services\Verification\ContactDeVerification;
use App\Services\Verification\CourrielVerification;
use App\Services\Verification\MasqueContact;
use App\Services\Verification\SecretsVerification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Voir sa convocation, choisir ou changer son creneau : apres un code, plus sur
 * la seule date de naissance.
 *
 * Le couple reference (ou matricule) + date de naissance se devine ou se
 * recopie : un camarade qui le connait voyait la convocation et deplacait le
 * rendez-vous. Quand l'ecole a active la verification du contact, ce couple ne
 * fait plus qu'IDENTIFIER le dossier ; l'acces se prouve par un code envoye au
 * contact du dossier (e-mail joignable, sinon WhatsApp) — le meme choix de canal
 * que la verification d'une demande (`ContactDeVerification`).
 *
 * Le code reussi rend un jeton d'acces (`JetonAccesRdv`), valable trente
 * minutes, que le site joint a chaque appel suivant. Rien n'est ecrit en base :
 * l'etat d'un code vit un quart d'heure dans le cache, sous un identifiant
 * aleatoire au format des verifications de demande. C'est ce qui permet au
 * site de reutiliser son ecran de code, et aux routes de verification de
 * reconnaitre ce code-ci (`estAcces`).
 */
class AccesRendezVous
{
    private const PREFIXE = 'rdv-acces:';

    private const DUREE_MINUTES = 15;

    private const TENTATIVES_MAX = 5;

    public function __construct(
        private readonly TenantScolariteSettings $reglages,
        private readonly ContactDeVerification $contacts,
        private readonly CourrielVerification $courriel,
        private readonly MailPulseVerifications $whatsapp,
        private readonly ReferencePublique $references,
    ) {}

    /** L'ecole a-t-elle active la verification ? Sinon, le portail garde son fonctionnement d'avant. */
    public function exige(): bool
    {
        return $this->reglages->verificationContactActive();
    }

    public function estAcces(?string $id): bool
    {
        return is_string($id) && $id !== '' && Cache::has(self::PREFIXE.$id);
    }

    /**
     * Envoie un code au contact du dossier.
     *
     * @return array<string, mixed> la reponse a rendre au site : `verification_*_requise`,
     *                              ou `contact_injoignable` quand le dossier n'a ni e-mail joignable ni
     *                              mobile — seul l'etablissement peut alors corriger le dossier.
     */
    public function demarrer(PorteurDeRendezVous&Model $porteur, string $reference): array
    {
        $contact = $this->contacts->pour($porteur);
        if ($contact === null) {
            return ['trouve' => true, 'statut' => 'contact_injoignable'];
        }

        /** @var CanalVerification $canal */
        $canal = $contact['canal'];
        $cle = $this->references->normaliser($reference);
        $id = (string) Str::uuid();

        // Un code par minute et par dossier : recharger la page ne doit pas
        // inonder la boite ou le WhatsApp de la famille. Le code deja parti
        // reste valable, on le redonne.
        $enCours = Cache::get(self::PREFIXE.'dossier:'.$cle);
        if (is_string($enCours) && $this->estAcces($enCours)) {
            $etat = Cache::get(self::PREFIXE.$enCours);

            return $this->reponse($enCours, $etat['canal'], $etat['destination']);
        }

        $etat = [
            'reference' => $cle,
            'canal' => $canal->value,
            'destination' => $contact['destination'],
            'tentatives' => 0,
        ];

        if (! $this->expedier($id, $etat)) {
            return ['trouve' => true, 'statut' => 'verification_indisponible'];
        }

        Cache::put(self::PREFIXE.'dossier:'.$cle, $id, now()->addMinute());

        return $this->reponse($id, $canal->value, $contact['destination']);
    }

    /**
     * Controle un code. Reussi : le jeton d'acces et la reference du dossier.
     *
     * @return array{0: int, 1: array<string, mixed>} statut HTTP et corps, au format des verifications de demande
     */
    public function verifier(string $id, string $code): array
    {
        $etat = Cache::get(self::PREFIXE.$id);
        if (! is_array($etat)) {
            return [422, ['verifie' => false, 'motif' => 'expire']];
        }
        if ($etat['tentatives'] >= self::TENTATIVES_MAX) {
            return [422, ['verifie' => false, 'motif' => 'trop_de_tentatives']];
        }

        $bon = $etat['canal'] === CanalVerification::Email->value
            ? SecretsVerification::concorde($etat['code_hash'] ?? null, $code)
            : $this->whatsapp->controler((string) ($etat['mailpulse_id'] ?? ''), $code)->ok;

        if (! $bon) {
            $etat['tentatives']++;
            Cache::put(self::PREFIXE.$id, $etat, now()->addMinutes(self::DUREE_MINUTES));

            return [422, ['verifie' => false, 'motif' => $etat['tentatives'] >= self::TENTATIVES_MAX ? 'trop_de_tentatives' : 'code_invalide']];
        }

        Cache::forget(self::PREFIXE.$id);
        Cache::forget(self::PREFIXE.'dossier:'.$etat['reference']);

        return [200, [
            'verifie' => true,
            'type' => 'rendez_vous',
            'reference' => $this->references->formater($etat['reference']),
            'jeton_acces' => JetonAccesRdv::emettre($etat['reference']),
        ]];
    }

    /** Nouveau code sur le meme canal. Rend l'attente en secondes, ou null si c'est parti. */
    public function renvoyer(string $id): ?int
    {
        $cle = 'rdv-acces-renvoi:'.hash('sha256', $id);
        if (RateLimiter::tooManyAttempts($cle, 1)) {
            return max(1, RateLimiter::availableIn($cle));
        }
        RateLimiter::hit($cle, 60);

        $etat = Cache::get(self::PREFIXE.$id);
        if (is_array($etat)) {
            $etat['tentatives'] = 0;
            $this->expedier($id, $etat);
        }

        return null;
    }

    /** @param  array<string, mixed>  $etat */
    private function expedier(string $id, array $etat): bool
    {
        if ($etat['canal'] === CanalVerification::Email->value) {
            $code = SecretsVerification::nouveauCode();
            $envoi = $this->courriel->expedierCodeAcces((string) $etat['destination'], $code, self::DUREE_MINUTES);
            $etat['code_hash'] = SecretsVerification::empreinte($code);
            $ok = $envoi->ok;
            $motif = $envoi->status;
        } else {
            $envoi = $this->whatsapp->creer((string) $etat['destination'], $id);
            $etat['mailpulse_id'] = (string) $envoi->id;
            $ok = $envoi->ok;
            $motif = $envoi->code;
        }

        if (! $ok) {
            Log::warning('Acces rendez-vous : code non parti', ['canal' => $etat['canal'], 'motif' => $motif]);

            return false;
        }

        Cache::put(self::PREFIXE.$id, $etat, now()->addMinutes(self::DUREE_MINUTES));

        return true;
    }

    /** @return array<string, mixed> */
    private function reponse(string $id, string $canal, string $destination): array
    {
        $email = $canal === CanalVerification::Email->value;

        return [
            'trouve' => true,
            'statut' => $email ? 'verification_email_requise' : 'verification_telephone_requise',
            'demande_id' => $id,
            $email ? 'email_masque' : 'telephone_masque' => $email ? MasqueContact::email($destination) : MasqueContact::telephone($destination),
        ];
    }
}
