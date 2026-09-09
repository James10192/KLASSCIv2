<?php

namespace App\Services\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use Illuminate\Support\Facades\DB;

class AffecteurDossiersRdv
{
    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly CatalogueCreneaux $catalogue,
        private readonly ReservateurRdv $reservateur,
        private readonly MessagerieRdv $mails,
    ) {
    }

    public function placerApresCommit(PorteurDeRendezVous $porteur): void
    {
        DB::afterCommit(fn () => $this->placerUn($porteur));
    }

    public function placerUn(PorteurDeRendezVous $porteur): bool
    {
        if (! $this->reglages->enabled()) {
            return false;
        }

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
     * @return array{places: int, sans_email: int, sans_creneau: int, deja: int}
     */
    public function placer(): array
    {
        $rapport = ['places' => 0, 'sans_email' => 0, 'sans_creneau' => 0, 'deja' => 0];

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
        $this->mails->confirmer($reservation, 'confirme');
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
