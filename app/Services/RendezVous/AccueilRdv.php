<?php

namespace App\Services\RendezVous;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * La liste du jour au guichet : qui est attendu, qui est venu, qui manque.
 *
 * Les statuts existaient (honoree, manquee) mais rien ne les posait : une
 * famille venue et une famille absente restaient toutes deux « confirmees », et
 * personne ne pouvait dire en fin de journee qui n'avait pas ete pris en charge.
 *
 * Deux choses sont volontairement refusees :
 * - marquer « absent » avant le debut du creneau (on ne manque pas un
 *   rendez-vous qui n'a pas commence) ;
 * - marquer quoi que ce soit sur un creneau d'un jour a venir.
 * Et une chose ne l'est pas : rien n'est marque absent d'office. La fin de
 * journee le propose, l'agent le decide — c'est lui qui sait qu'une famille
 * est dans la file d'attente.
 */
class AccueilRdv
{
    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly ReservateurRdv $reservateur,
        private readonly FileConvocationsRdv $convocations,
        private readonly CatalogueCreneaux $catalogue,
    ) {
    }

    /**
     * @return array{creneaux: Collection<int, ESBTPRdvCreneau>, reprogrammees: Collection<int, ESBTPRdvReservation>, compteurs: array{attendus: int, recus: int, absents: int, a_recevoir: int, en_retard: int, reprogrammees: int}}
     */
    public function journee(Carbon $jour): array
    {
        $creneaux = ESBTPRdvCreneau::query()
            ->whereDate('date', $jour->toDateString())
            ->with(['reservations' => fn ($q) => $q->occupantes()
                ->with(array_merge(['accueilliPar:id,name', 'dernierCreneauManque'], ContactsFamilleRdv::chargements()))
                ->orderBy('nom')->orderBy('prenoms')])
            ->orderBy('heure_debut')
            ->get()
            ->filter(fn (ESBTPRdvCreneau $c) => $c->reservations->isNotEmpty())
            ->values();

        // Les absentes de ce jour deja reprogrammees ailleurs : sans elles, la
        // journee perdrait la trace des familles qu'elle n'a pas prises en charge.
        // Une seconde absence deplace cette trace vers le jour de la seconde.
        $reprogrammees = ESBTPRdvReservation::query()
            ->occupantes()
            ->whereHas('dernierCreneauManque', fn ($q) => $q->whereDate('date', $jour->toDateString()))
            ->whereHas('creneau', fn ($q) => $q->whereDate('date', '!=', $jour->toDateString()))
            ->with(array_merge(['creneau'], ContactsFamilleRdv::chargements()))
            ->orderBy('nom')->orderBy('prenoms')
            ->get();

        $toutes = $creneaux->flatMap->reservations;
        $compteurs = [
            'attendus' => $toutes->count(),
            'recus' => $toutes->where('statut', StatutReservationRdv::Honoree)->count(),
            'absents' => $toutes->where('statut', StatutReservationRdv::Manquee)->count(),
            'a_recevoir' => $toutes->where('statut', StatutReservationRdv::Confirmee)->count(),
            'en_retard' => $toutes->filter(fn (ESBTPRdvReservation $r) => $this->enRetard($r))->count(),
            'reprogrammees' => $reprogrammees->count(),
        ];

        return ['creneaux' => $creneaux, 'reprogrammees' => $reprogrammees, 'compteurs' => $compteurs];
    }

    /** Attendue, creneau commence depuis plus que la tolerance reglee, et pas encore marquee. */
    public function enRetard(ESBTPRdvReservation $reservation): bool
    {
        return $reservation->statut === StatutReservationRdv::Confirmee
            && Carbon::now()->gt($this->debut($reservation->creneau)->addMinutes($this->reglages->graceMinutes()));
    }

    public function creneauTermine(ESBTPRdvCreneau $creneau): bool
    {
        return Carbon::now()->gte(Carbon::parse($creneau->date->toDateString().' '.$creneau->heureFinHi().':00'));
    }

    /** @return string|null le refus, ou null si la famille est marquee reçue */
    public function marquerRecu(ESBTPRdvReservation $reservation, int $agentId): ?string
    {
        if ($refus = $this->horsDuJour($reservation)) {
            return $refus;
        }

        return $this->transition($reservation, StatutReservationRdv::Honoree, $agentId);
    }

    /** @return string|null le refus, ou null si la famille est marquee absente */
    public function marquerAbsent(ESBTPRdvReservation $reservation, int $agentId): ?string
    {
        if ($refus = $this->horsDuJour($reservation)) {
            return $refus;
        }
        if (Carbon::now()->lt($this->debut($reservation->creneau))) {
            return 'Le créneau n\'a pas commencé : la famille ne peut pas encore être absente.';
        }

        return $this->transition($reservation, StatutReservationRdv::Manquee, $agentId);
    }

    /** Retour a « attendue », pour corriger un clic. */
    public function annulerMarque(ESBTPRdvReservation $reservation): ?string
    {
        return $this->transition($reservation, StatutReservationRdv::Confirmee, null);
    }

    /**
     * Fin de journee : les familles encore attendues sur un creneau TERMINE
     * passent absentes. Les creneaux en cours ne sont pas touches.
     */
    public function cloturer(Carbon $jour, int $agentId): int
    {
        if ($jour->isFuture() && ! $jour->isToday()) {
            return 0;
        }

        $n = 0;
        foreach ($this->journee($jour)['creneaux'] as $creneau) {
            if (! $this->creneauTermine($creneau)) {
                continue;
            }
            foreach ($creneau->reservations as $reservation) {
                if ($reservation->statut === StatutReservationRdv::Confirmee
                    && $this->transition($reservation, StatutReservationRdv::Manquee, $agentId) === null) {
                    $n++;
                }
            }
        }

        return $n;
    }

    /**
     * Les prochains creneaux avec de la place, pour reprogrammer.
     *
     * @return list<array{id: int, date: string, libelle: string, heure: string, libres: int}>
     */
    public function creneauxProposes(int $maximum = 40): array
    {
        $libres = array_slice($this->catalogue->placesLibres(), 0, $maximum, true);
        if ($libres === []) {
            return [];
        }

        return ESBTPRdvCreneau::query()->whereKey(array_keys($libres))->orderBy('date')->orderBy('heure_debut')->get()
            ->map(fn (ESBTPRdvCreneau $c) => [
                'id' => (int) $c->id,
                'date' => $c->date->toDateString(),
                'libelle' => ucfirst($c->date->translatedFormat('l j F')),
                'heure' => $c->heureDebutHi().' – '.$c->heureFinHi(),
                'libres' => $libres[(int) $c->id],
            ])->all();
    }

    /**
     * Reprogramme sur un autre creneau et renvoie une convocation « deplace ».
     * L'absence eventuelle reste comptee (absences, dernier creneau manque).
     *
     * @return string|null le refus, ou null si c'est fait
     */
    public function reprogrammer(ESBTPRdvReservation $reservation, int $creneauId): ?string
    {
        $resultat = $this->reservateur->replacerAuGuichet($reservation, $creneauId);
        if (! $resultat['ok']) {
            return match ($resultat['code']) {
                'complet' => 'Ce créneau vient d\'être rempli. Choisissez-en un autre.',
                'trop_tot' => 'Ce créneau a déjà commencé.',
                'ferme' => 'Ce créneau est fermé.',
                default => 'Ce rendez-vous n\'existe plus.',
            };
        }

        $this->convocations->confirmer($resultat['reservation'], 'deplace');

        return null;
    }

    private function transition(ESBTPRdvReservation $reservation, StatutReservationRdv $vers, ?int $agentId): ?string
    {
        return DB::transaction(function () use ($reservation, $vers, $agentId) {
            $r = ESBTPRdvReservation::query()->occupantes()->whereKey($reservation->id)->lockForUpdate()->first();
            if ($r === null) {
                return 'Ce rendez-vous a été annulé ou déplacé entre-temps.';
            }
            if ($r->statut === $vers) {
                return null;
            }

            $absencesAvant = (int) $r->absences;
            $etaitAbsent = $r->statut === StatutReservationRdv::Manquee;

            $r->forceFill([
                'statut' => $vers,
                'accueilli_at' => $vers === StatutReservationRdv::Confirmee ? null : now(),
                'accueilli_par' => $vers === StatutReservationRdv::Confirmee ? null : $agentId,
                'absences' => match (true) {
                    $vers === StatutReservationRdv::Manquee => $absencesAvant + 1,
                    $etaitAbsent => max(0, $absencesAvant - 1),
                    default => $absencesAvant,
                },
                'dernier_creneau_manque_id' => $vers === StatutReservationRdv::Manquee ? $r->creneau_id
                    : ($etaitAbsent && $absencesAvant <= 1 ? null : $r->dernier_creneau_manque_id),
            ])->save();

            return null;
        });
    }

    private function horsDuJour(ESBTPRdvReservation $reservation): ?string
    {
        $reservation->loadMissing('creneau');
        if ($reservation->creneau === null) {
            return 'Ce rendez-vous n\'a plus de créneau.';
        }
        if ($reservation->creneau->date->isFuture() && ! $reservation->creneau->date->isToday()) {
            return 'Ce rendez-vous est pour un autre jour.';
        }

        return null;
    }

    private function debut(ESBTPRdvCreneau $creneau): Carbon
    {
        return Carbon::parse($creneau->date->toDateString().' '.$creneau->heureDebutHi().':00');
    }
}
