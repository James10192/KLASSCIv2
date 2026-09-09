<?php

namespace App\Services\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Exceptions\ReglagesRdvIncomplets;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPRdvCreneau;
use Carbon\Carbon;

class AffecteurDossiersRdv
{
    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly ReservateurRdv $reservateur,
        private readonly MessagerieRdv $mails,
    ) {
    }

    /**
     * @return array{date: string, heure_debut: string, heure_fin: string}|null
     */
    public function placerUn(PorteurDeRendezVous $porteur): ?array
    {
        $existante = ESBTPRdvReservation::query()
            ->occupantes()
            ->where($porteur->colonneReservationRdv(), $porteur->clesReservationRdv()[$porteur->colonneReservationRdv()])
            ->with('creneau')
            ->first();
        if ($existante !== null) {
            return $this->presenter($existante);
        }

        $email = trim((string) $porteur->emailRdv());
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $creneauId = $this->prochainCreneau($this->placesRestantes());
        if ($creneauId === null) {
            return null;
        }

        $resultat = $this->reservateur->placer($porteur, $creneauId);
        if (! $resultat['ok'] || ! isset($resultat['reservation'])) {
            return null;
        }

        $porteur->assurerReferencePublique();
        $this->mails->confirmer($resultat['reservation'], 'confirme');
        $porteur->marquerInviteRdv();

        return $this->presenter($resultat['reservation']);
    }

    /**
     * @return array{date: string, heure_debut: string, heure_fin: string}
     */
    private function presenter(ESBTPRdvReservation $reservation): array
    {
        $creneau = $reservation->creneau;

        return [
            'date' => $creneau?->date?->toDateString() ?? '',
            'heure_debut' => $creneau?->heureDebutHi() ?? '',
            'heure_fin' => $creneau?->heureFinHi() ?? '',
        ];
    }

    /**
     * @return array{places: int, sans_email: int, sans_creneau: int, deja: int}
     */
    public function placer(bool $ecrire = false): array
    {
        $rapport = ['places' => 0, 'sans_email' => 0, 'sans_creneau' => 0, 'deja' => 0];
        $restantes = $this->placesRestantes();

        $this->chaquePorteur(function (PorteurDeRendezVous $porteur) use ($ecrire, &$rapport, &$restantes) {
            $email = trim((string) $porteur->emailRdv());
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rapport['sans_email']++;

                return;
            }

            $creneauId = $this->prochainCreneau($restantes);
            if ($creneauId === null) {
                $rapport['sans_creneau']++;

                return;
            }

            if (! $ecrire) {
                $restantes[$creneauId]--;
                $rapport['places']++;

                return;
            }

            $resultat = $this->reservateur->placer($porteur, $creneauId);
            if (! $resultat['ok']) {
                if (($resultat['code'] ?? '') === 'deja_reserve') {
                    $rapport['deja']++;

                    return;
                }
                $rapport['sans_creneau']++;

                return;
            }

            $restantes[$creneauId]--;
            $porteur->assurerReferencePublique();
            $this->mails->confirmer($resultat['reservation'], 'confirme');
            $porteur->marquerInviteRdv();
            $rapport['places']++;
        });

        return $rapport;
    }

    /**
     * @param  callable(PorteurDeRendezVous): void  $suite
     */
    private function chaquePorteur(callable $suite): void
    {
        ESBTPCandidature::query()
            ->where('statut', ESBTPCandidature::STATUT_EN_ATTENTE)
            ->whereDoesntHave('reservations', fn ($q) => $q->occupantes())
            ->orderBy('id')
            ->each(function (ESBTPCandidature $c) use ($suite) {
                $suite($c);
            });

        ESBTPReinscriptionDemande::query()
            ->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)
            ->whereDoesntHave('reservations', fn ($q) => $q->occupantes())
            ->with('etudiant')
            ->orderBy('id')
            ->each(function (ESBTPReinscriptionDemande $d) use ($suite) {
                $suite($d);
            });
    }

    /**
     * @return array<int, int>
     */
    private function placesRestantes(): array
    {
        try {
            $regle = $this->reglages->pourGeneration();
        } catch (ReglagesRdvIncomplets) {
            return [];
        }

        $debut = Carbon::today();
        if ($regle->plancher->gt($debut)) {
            $debut = $regle->plancher->copy();
        }

        $creneaux = ESBTPRdvCreneau::query()
            ->where('ouvert', true)
            ->whereDate('date', '>=', $debut->toDateString())
            ->whereDate('date', '<=', $regle->fermeture->toDateString())
            ->withCount(['reservations as prises' => fn ($q) => $q->occupantes()])
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get();

        $restantes = [];
        foreach ($creneaux as $creneau) {
            $libre = (int) $creneau->capacite - (int) ($creneau->prises ?? 0);
            if ($libre > 0 && ! $this->dejaPasse($creneau)) {
                $restantes[(int) $creneau->id] = $libre;
            }
        }

        return $restantes;
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

    private function dejaPasse(ESBTPRdvCreneau $creneau): bool
    {
        return Carbon::now()->gte(Carbon::parse($creneau->date->toDateString().' '.$creneau->heureDebutHi().':00'));
    }
}
