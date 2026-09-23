<?php

namespace App\Services\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;

class AffecteurDossiersRdv
{
    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly CatalogueCreneaux $catalogue,
        private readonly ReservateurRdv $reservateur,
        private readonly MessagerieRdv $mails,
    ) {
    }

    private function placerUn(PorteurDeRendezVous $porteur): bool
    {
        $existante = $this->reservateur->reservationActive($porteur);
        if ($existante !== null) {
            // N'arrive ici qu'une reservation d'avant le suivi, jamais convoquee
            // (voir dejaTraitee()) : c'est la seule qu'il faut encore poser.
            $this->convoquer($porteur, $existante);

            return true;
        }

        $creneauId = $this->prochainCreneau($this->catalogue->placesLibres());
        if ($creneauId === null) {
            return false;
        }

        $resultat = $this->reservateur->placer($porteur, $creneauId);
        if (! $resultat['ok'] || ! isset($resultat['reservation'])) {
            return false;
        }

        $this->convoquer($porteur, $resultat['reservation']);

        return true;
    }

    /**
     * La mention des dossiers places sans e-mail, pour les deux compte-rendus
     * (ecran et CLI). Vide quand il n'y en a aucun : « dont 0 sans e-mail » ne
     * dit rien, et ces dossiers n'ont justement pas de convocation en attente.
     */
    public static function mentionAPrevenir(int $aPrevenir): string
    {
        return $aPrevenir > 0
            ? sprintf(' Dont %d sans e-mail, à prévenir par téléphone (liste « Familles à prévenir »).', $aPrevenir)
            : '';
    }

    /**
     * Place les dossiers en attente et POSE leur convocation en attente d'envoi.
     *
     * Rien n'est envoye ici. Avant, chaque placement programmait son envoi dans
     * `terminating`, et un lot de plusieurs centaines tournait dans un seul
     * processus PHP — tue en route, sans trace. L'envoi passe desormais par
     * FileConvocationsRdv, par paquets.
     *
     * `refus` dit pourquoi rien n'a ete tente. Avant, un canal ferme etait compte
     * « sans creneau » pour chaque dossier : l'ecole cherchait des creneaux qui
     * existaient.
     *
     * Un dossier sans e-mail est place lui aussi : sa convocation est posee
     * « sans e-mail » (MessagerieRdv::planifier) et il entre dans la liste des
     * familles a prevenir par telephone. Avant, il n'etait pas place du tout, et
     * rien ne le signalait hors du compte-rendu de ce bouton. `a_prevenir`
     * compte ces dossiers ; il est inclus dans `places`.
     *
     * @return array{places: int, a_prevenir: int, sans_creneau: int, deja: int, refus: ?string}
     */
    public function placer(): array
    {
        $rapport = ['places' => 0, 'a_prevenir' => 0, 'sans_creneau' => 0, 'deja' => 0, 'refus' => null];

        if (! $this->reglages->enabled()) {
            $rapport['refus'] = 'La prise de rendez-vous est fermée. Ouvrez-la dans les réglages avant de placer les dossiers.';

            return $rapport;
        }

        if ($this->catalogue->placesLibres() === []) {
            $rapport['refus'] = 'Aucune place libre sur les créneaux à venir. Générez ou ouvrez des créneaux d\'abord.';

            return $rapport;
        }

        $this->chaquePorteur(function (PorteurDeRendezVous $porteur) use (&$rapport) {
            $existante = $this->reservateur->reservationActive($porteur);
            if ($existante !== null && $this->dejaTraitee($porteur, $existante)) {
                $rapport['deja']++;

                return;
            }

            if ($this->placerUn($porteur)) {
                $rapport['places']++;
                if (! $this->emailValide($porteur)) {
                    $rapport['a_prevenir']++;
                }

                return;
            }

            $rapport['sans_creneau']++;
        });

        return $rapport;
    }

    /**
     * La reservation dit elle-meme ou en est sa convocation. `rdv_invite_at`, pose
     * seulement apres un envoi REUSSI, ne suffisait pas : une convocation en
     * attente ou en echec etait reprise a chaque clic, remise a zero de ses
     * tentatives et de son motif, et recomptee « placee ». Il ne sert plus que
     * pour les reservations d'avant le suivi, dont l'etat est inconnu.
     */
    private function dejaTraitee(PorteurDeRendezVous $porteur, ESBTPRdvReservation $reservation): bool
    {
        return $reservation->convocation_statut !== null || $porteur->dejaInviteRdv();
    }

    private function convoquer(PorteurDeRendezVous $porteur, ESBTPRdvReservation $reservation): void
    {
        $porteur->assurerReferencePublique();
        $this->mails->planifier($reservation, 'confirme');
    }

    private function emailValide(PorteurDeRendezVous $porteur): bool
    {
        $email = trim((string) $porteur->emailRdv());

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param  callable(PorteurDeRendezVous): void  $suite
     */
    private function chaquePorteur(callable $suite): void
    {
        $anneeId = $this->catalogue->anneeDesCreneaux()?->id;
        if ($anneeId === null) {
            return;
        }

        ESBTPCandidature::query()
            ->where('statut', ESBTPCandidature::STATUT_EN_ATTENTE)
            // Contact jamais prouve : l'ecole confirme d'abord (« Confirmer le contact »).
            ->contactUtilisable()
            ->where('annee_universitaire_id', $anneeId)
            ->where(function ($q) {
                $q->whereDoesntHave('reservations', fn ($r) => $r->occupantes())
                    ->orWhereNull('rdv_invite_at');
            })
            ->orderBy('id')
            ->each(fn (ESBTPCandidature $c) => $suite($c));

        ESBTPReinscriptionDemande::query()
            ->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)
            ->contactUtilisable()
            ->where('annee_universitaire_id', $anneeId)
            ->where(function ($q) {
                $q->whereDoesntHave('reservations', fn ($r) => $r->occupantes())
                    ->orWhereNull('rdv_invite_at');
            })
            ->with('etudiant')
            ->orderBy('id')
            ->each(fn (ESBTPReinscriptionDemande $d) => $suite($d));
    }

    /** @param  array<int, int>  $restantes */
    private function prochainCreneau(array $restantes): ?int
    {
        foreach ($restantes as $id => $libre) {
            if ($libre > 0) {
                return (int) $id;
            }
        }

        return null;
    }
}
