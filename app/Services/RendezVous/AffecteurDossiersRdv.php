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
        if (! $this->emailValide($porteur)) {
            return false;
        }

        $existante = $this->reservateur->reservationActive($porteur);
        if ($existante !== null) {
            if (! $porteur->dejaInviteRdv()) {
                $this->convoquer($porteur, $existante);
            }

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
     * @return array{places: int, sans_email: int, sans_creneau: int, deja: int, refus: ?string}
     */
    public function placer(): array
    {
        $rapport = ['places' => 0, 'sans_email' => 0, 'sans_creneau' => 0, 'deja' => 0, 'refus' => null];

        if (! $this->reglages->enabled()) {
            $rapport['refus'] = 'La prise de rendez-vous est fermée. Ouvrez-la dans les réglages avant de placer les dossiers.';

            return $rapport;
        }

        if ($this->catalogue->placesLibres() === []) {
            $rapport['refus'] = 'Aucune place libre sur les créneaux à venir. Générez ou ouvrez des créneaux d\'abord.';

            return $rapport;
        }

        $this->chaquePorteur(function (PorteurDeRendezVous $porteur) use (&$rapport) {
            if (! $this->emailValide($porteur)) {
                $rapport['sans_email']++;

                return;
            }

            $existante = $this->reservateur->reservationActive($porteur);
            if ($existante !== null && $porteur->dejaInviteRdv()) {
                $rapport['deja']++;

                return;
            }

            if ($this->placerUn($porteur)) {
                $rapport['places']++;

                return;
            }

            $rapport['sans_creneau']++;
        });

        return $rapport;
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
            ->where('annee_universitaire_id', $anneeId)
            ->where(function ($q) {
                $q->whereDoesntHave('reservations', fn ($r) => $r->occupantes())
                    ->orWhereNull('rdv_invite_at');
            })
            ->orderBy('id')
            ->each(fn (ESBTPCandidature $c) => $suite($c));

        ESBTPReinscriptionDemande::query()
            ->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)
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
