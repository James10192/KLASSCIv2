<?php

namespace App\Services\Portail;

use App\Contracts\PorteurDeRendezVous;
use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\Emails\AnalyseurEmail;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\RendezVous\ReprisesConvocationsRetenues;
use App\Services\RendezVous\RendezVousReglages;
use App\Services\RendezVous\ReservateurRdv;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\Verification\DemarrageVerification;
use App\Services\Verification\MasqueContact;
use App\Services\Verification\VerificationDemarree;
use App\Support\SeauDeDebit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Une famille revient sur le portail pour suivre sa demande deja deposee :
 * voir ou elle en est, quelle adresse l'ecole a, la verifier ou la corriger,
 * et recevoir sa convocation.
 *
 * Identifiee par la reference du dossier et la date de naissance — ou, pour
 * une reinscription, par le matricule et la date de naissance, que l'etudiant
 * connait mieux qu'une reference. Rien d'autre n'est rendu que ce que la
 * famille a elle-meme saisi : l'adresse est masquee, jamais lisible.
 *
 * L'adresse d'une reinscription est posee sur la DEMANDE (`email_contact`),
 * jamais sur la fiche de l'etudiant : le site public ne reecrit pas le contact
 * officiel d'un etudiant.
 */
class SuiviDossierPortail
{
    /** Renvois de convocation a la demande de la famille, par reservation et par jour. */
    private const CONVOCATIONS_PAR_JOUR = 3;

    public function __construct(
        private readonly ReservateurRdv $reservateur,
        private readonly PortailReinscriptionService $reinscriptions,
        private readonly ReferencePublique $references,
        private readonly DemarrageVerification $verification,
        private readonly FileConvocationsRdv $convocations,
        private readonly RendezVousReglages $rdv,
        private readonly AnalyseurEmail $emails,
        private readonly ReprisesConvocationsRetenues $reprises,
    ) {
    }

    public static function seau(string $identifiant): SeauDeDebit
    {
        return SeauDeDebit::parIdentifiant('suivi-id:'.hash('sha256', Str::upper(trim($identifiant))), 10, 900);
    }

    /** @return (Model&PorteurDeRendezVous)|null */
    public function retrouver(string $identifiant, string $dateNaissance): ?Model
    {
        $porteur = $this->reservateur->porteurConcordant($identifiant, $dateNaissance);
        if ($porteur instanceof Model) {
            return $porteur;
        }

        // Reinscription : le matricule, sur la demande de l'annee visee.
        $etudiant = $this->reinscriptions->identifier(trim($identifiant), $dateNaissance);
        $annee = $this->reinscriptions->anneeCible();
        if ($etudiant === null || $annee === null) {
            return null;
        }

        return ESBTPReinscriptionDemande::query()
            ->where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $annee->id)
            ->latest('id')
            ->first();
    }

    /** @return array<string, mixed> */
    public function situation(Model $porteur): array
    {
        /** @var Model&PorteurDeRendezVous $porteur */
        $reservation = $this->reservateur->reservationActive($porteur)?->load('creneau');
        $email = $porteur->emailRdv();
        $emailJoignable = $this->emails->analyser($email)->joignable();

        return [
            'trouve' => true,
            'type' => $porteur instanceof ESBTPCandidature ? 'inscription' : 'reinscription',
            'reference' => $this->references->formater($porteur->assurerReferencePublique()),
            'statut' => $this->statut($porteur),
            'contact' => [
                'email_masque' => $emailJoignable ? MasqueContact::email($email) : null,
                'email_verifie' => $porteur->email_verifie_at !== null,
                'a_confirmer' => $porteur->contactAConfirmer(),
            ],
            'verification_active' => $this->verification->active(),
            'prise_rdv_ouverte' => $this->rdv->enabled(),
            'rendez_vous' => $reservation === null ? null : $this->presenter($reservation),
            'peut_recevoir_convocation' => $this->refusConvocation($porteur, $reservation) === null,
        ];
    }

    /**
     * Pose l'adresse donnee par la famille, puis envoie un code pour la prouver.
     * Sans verification activee par l'ecole, l'adresse vaut tout de suite et les
     * convocations retenues repartent.
     *
     * @return array{code: string, verification?: VerificationDemarree}
     */
    public function changerEmail(Model $porteur, string $email): array
    {
        /** @var Model&PorteurDeRendezVous $porteur */
        if ($porteur->dossierClos()) {
            return ['code' => 'dossier_clos'];
        }

        $email = mb_strtolower(trim($email));
        if (mb_strtolower(trim((string) $porteur->emailRdv())) === $email) {
            return $this->verifier($porteur);
        }

        // Une adresse neuve n'a encore rien prouve.
        $colonne = $porteur instanceof ESBTPCandidature ? 'email' : 'email_contact';
        $porteur->forceFill([$colonne => $email, 'email_verifie_at' => null])->save();
        $porteur->contactModifieAuDepot = true;

        if (! $this->verification->active()) {
            $this->reprises->reprendre($porteur);

            return ['code' => 'enregistre'];
        }

        // Adresse changee : « a reconfirmer », un code part, la convocation
        // attend que la famille le saisisse (DemarrageVerification).
        $demarree = $this->verification->apresDepot($porteur);

        return $demarree === null
            ? ['code' => 'envoi_impossible']
            : ['code' => 'code_envoye', 'verification' => $demarree];
    }

    /** Relance la verification de l'adresse deja au dossier, sans la changer. */
    public function verifier(Model $porteur): array
    {
        /** @var Model&PorteurDeRendezVous $porteur */
        if ($porteur->email_verifie_at !== null) {
            return ['code' => 'deja_verifie'];
        }
        if (! $this->verification->active()) {
            return ['code' => 'verification_inactive'];
        }

        // Une demande deja marquee le reste ; une demande jamais marquee (deposee
        // avant l'activation) ne se met pas a retenir sa convocation : le code
        // ne fait que dater son contact.
        $demarree = $this->verification->demarrer($porteur, $porteur->contactMarque());

        return $demarree === null
            ? ['code' => $porteur->fresh()?->email_verifie_at !== null ? 'deja_verifie' : 'envoi_impossible']
            : ['code' => 'code_envoye', 'verification' => $demarree];
    }

    /** Renvoie la convocation (e-mail + lien PDF) du rendez-vous a venir. */
    public function envoyerConvocation(Model $porteur): string
    {
        /** @var Model&PorteurDeRendezVous $porteur */
        $reservation = $this->reservateur->reservationActive($porteur)?->load('creneau');
        if (($refus = $this->refusConvocation($porteur, $reservation)) !== null) {
            return $refus;
        }

        $cle = 'suivi-convocation:'.$reservation->id;
        if (RateLimiter::tooManyAttempts($cle, self::CONVOCATIONS_PAR_JOUR)) {
            return 'trop_de_demandes';
        }
        RateLimiter::hit($cle, 86400);

        // L'adresse du dossier, qui peut avoir ete corrigee depuis la reservation.
        $reservation->forceFill(['email' => $porteur->emailRdv()])->save();
        $this->convocations->confirmer($reservation, 'confirme');

        return $reservation->fresh()?->convocation_statut === StatutConvocationRdv::EnAttente ? 'envoyee' : 'envoi_impossible';
    }

    private function refusConvocation(Model $porteur, ?ESBTPRdvReservation $reservation): ?string
    {
        /** @var Model&PorteurDeRendezVous $porteur */
        return match (true) {
            $reservation === null => 'sans_rendez_vous',
            $reservation->creneau === null || $reservation->creneau->aCommence() => 'rendez_vous_passe',
            $porteur->dossierClos() => 'dossier_clos',
            $porteur->contactAConfirmer() => 'contact_a_confirmer',
            ! $this->emails->analyser($porteur->emailRdv())->joignable() => 'sans_email',
            default => null,
        };
    }

    /** @return array{code: string, libelle: string} */
    private function statut(Model $porteur): array
    {
        $code = (string) $porteur->statut;
        $inscription = $porteur instanceof ESBTPCandidature;

        return ['code' => $code, 'libelle' => match ($code) {
            'en_attente' => 'En cours d\'examen par l\'établissement',
            'acceptee' => 'Acceptée — l\'établissement finalise votre inscription',
            'rejetee' => 'Non retenue — contactez l\'établissement',
            'convertie' => $inscription ? 'Inscription enregistrée' : 'Réinscription enregistrée',
            default => 'En cours de traitement',
        }];
    }

    /** @return array<string, mixed> */
    private function presenter(ESBTPRdvReservation $reservation): array
    {
        $creneau = $reservation->creneau;

        return [
            'date' => $creneau?->date->toDateString(),
            'heure_debut' => $creneau?->heureDebutHi(),
            'heure_fin' => $creneau?->heureFinHi(),
            'statut' => $reservation->statut->value,
            'convocation' => $reservation->convocation_statut?->value,
            'peut_modifier' => $this->reservateur->peutModifier($reservation),
        ];
    }
}
