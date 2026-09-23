<?php

namespace App\Services\RendezVous;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReprogrammation;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * La liste du jour au guichet : qui est attendu, qui est venu, qui manque.
 *
 * Une seule chose se pose a la main : « reçue ». L'absence se DEDUIT — creneau
 * termine, famille pas reçue, dossier toujours en attente. Stockee, elle
 * dependait d'un clic de fin de journee : un jour sans ce clic, les familles
 * absentes restaient « confirmees » sur un creneau passe, et plus aucun ecran
 * ne les montrait. Deduite, elle ne peut pas etre oubliee.
 *
 * Le dossier compte : une famille jamais cochee dont le dossier a avance est
 * venue, ou a ete traitee autrement. La dire absente ferait reprogrammer des
 * familles deja inscrites — c'est le cas de toutes les reservations d'avant
 * cet ecran, que personne ne cochait.
 */
class AccueilRdv
{
    public const RECUE = 'recu';

    public const NON_VENUE = 'non_venue';

    public const TRAITEE = 'traite';

    public const ATTENDUE = 'attendu';

    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly ReservateurRdv $reservateur,
        private readonly FileConvocationsRdv $convocations,
        private readonly CatalogueCreneaux $catalogue,
    ) {
    }

    /**
     * @return array{creneaux: Collection<int, ESBTPRdvCreneau>, reprogrammees: Collection<int, ESBTPRdvReservation>, enSouffrance: Collection<int, object>, compteurs: array<string, int>}
     */
    public function journee(Carbon $jour): array
    {
        $creneaux = ESBTPRdvCreneau::query()
            ->whereDate('date', $jour->toDateString())
            ->with(['reservations' => fn ($q) => $q->occupantes()
                ->with(array_merge(['accueilliPar:id,name', 'prevenuePar:id,name'], ContactsFamilleRdv::chargements()))
                ->withCount(['reprogrammations as absences' => fn ($r) => $r->where('non_venue', true)])
                ->orderBy('nom')->orderBy('prenoms')])
            ->orderBy('heure_debut')
            ->get()
            ->filter(fn (ESBTPRdvCreneau $c) => $c->reservations->isNotEmpty())
            ->values();

        $reprogrammees = $this->reprogrammeesDepuis($jour);
        $toutes = $creneaux->flatMap->reservations;
        $parEtat = $toutes->countBy(fn (ESBTPRdvReservation $r) => $this->etat($r));

        return [
            'creneaux' => $creneaux,
            'reprogrammees' => $reprogrammees,
            'enSouffrance' => $this->joursEnSouffrance($jour),
            'compteurs' => [
                'attendus' => $toutes->count(),
                'recus' => $parEtat[self::RECUE] ?? 0,
                'non_venues' => $parEtat[self::NON_VENUE] ?? 0,
                'traitees' => $parEtat[self::TRAITEE] ?? 0,
                'a_recevoir' => $parEtat[self::ATTENDUE] ?? 0,
                'en_retard' => $toutes->filter(fn (ESBTPRdvReservation $r) => $this->enRetard($r))->count(),
                'reprogrammees' => $reprogrammees->count(),
            ],
        ];
    }

    public function etat(ESBTPRdvReservation $r): string
    {
        if ($r->statut === StatutReservationRdv::Honoree) {
            return self::RECUE;
        }
        if (! $this->creneauTermine($r->creneau)) {
            return self::ATTENDUE;
        }

        return $this->dossierEnAttente($r) ? self::NON_VENUE : self::TRAITEE;
    }

    /** Attendue, creneau commence depuis plus que la tolerance reglee, pas encore reçue. */
    public function enRetard(ESBTPRdvReservation $r): bool
    {
        return $r->statut === StatutReservationRdv::Confirmee
            && ! $this->creneauTermine($r->creneau)
            && Carbon::now()->gt($this->debut($r->creneau)->addMinutes($this->reglages->graceMinutes()));
    }

    public function aCommence(ESBTPRdvCreneau $creneau): bool
    {
        return Carbon::now()->gte($this->debut($creneau));
    }

    public function creneauTermine(ESBTPRdvCreneau $creneau): bool
    {
        return Carbon::now()->gte(Carbon::parse($creneau->date->toDateString().' '.$creneau->heureFinHi().':00'));
    }

    /**
     * Une famille arrivee apres la fin de son creneau se coche encore : c'est
     * justement ce qui rend l'absence deduite sans risque.
     *
     * @return string|null le refus, ou null si la famille est marquee reçue
     */
    public function marquerRecu(ESBTPRdvReservation $reservation, int $agentId): ?string
    {
        $reservation->loadMissing('creneau');
        if ($reservation->creneau === null) {
            return 'Ce rendez-vous n\'a plus de créneau.';
        }
        if ($reservation->creneau->date->isFuture() && ! $reservation->creneau->date->isToday()) {
            return 'Ce rendez-vous est pour un autre jour.';
        }

        return $this->transition($reservation, StatutReservationRdv::Honoree, $agentId);
    }

    /** Retour a « attendue », pour corriger un clic. */
    public function annulerRecu(ESBTPRdvReservation $reservation): ?string
    {
        return $this->transition($reservation, StatutReservationRdv::Confirmee, null);
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
     * Deplace sur un autre creneau, renvoie une convocation « deplace », et
     * journalise le deplacement — en non-venue si le creneau quitte etait
     * termine sans que la famille soit reçue.
     *
     * @return string|null le refus, ou null si c'est fait
     */
    public function reprogrammer(ESBTPRdvReservation $reservation, int $creneauId, ?int $agentId = null): ?string
    {
        $resultat = $this->reservateur->replacerAuGuichet($reservation, $creneauId);
        if (! $resultat['ok']) {
            return match ($resultat['code']) {
                'complet' => 'Ce créneau vient d\'être rempli. Choisissez-en un autre.',
                'trop_tot' => 'Ce créneau a déjà commencé.',
                'ferme' => 'Ce créneau est fermé.',
                'recue' => 'Cette famille a déjà été reçue : il n\'y a rien à reprogrammer.',
                default => 'Ce rendez-vous n\'existe plus.',
            };
        }

        $quitte = ESBTPRdvCreneau::find($resultat['creneau_quitte_id']);
        ESBTPRdvReprogrammation::create([
            'reservation_id' => $resultat['reservation']->id,
            'creneau_quitte_id' => $resultat['creneau_quitte_id'],
            'creneau_nouveau_id' => $resultat['reservation']->creneau_id,
            'non_venue' => $quitte !== null && $this->creneauTermine($quitte),
            'par' => $agentId,
        ]);

        $this->convocations->confirmer($resultat['reservation'], 'deplace');

        return null;
    }

    /**
     * Toutes les non-venues d'un jour, chacune sur le premier creneau libre qui
     * reste. S'arrete quand il n'y a plus de place.
     *
     * @return array{faites: int, sans_place: int}
     */
    public function reprogrammerNonVenues(Carbon $jour, int $agentId): array
    {
        $rapport = ['faites' => 0, 'sans_place' => 0];
        $nonVenues = $this->journee($jour)['creneaux']->flatMap->reservations
            ->filter(fn (ESBTPRdvReservation $r) => $this->etat($r) === self::NON_VENUE)
            ->values();

        foreach ($nonVenues as $i => $reservation) {
            $creneauId = array_key_first(array_filter($this->catalogue->placesLibres(), fn ($libre) => $libre > 0));
            if ($creneauId === null) {
                $rapport['sans_place'] = $nonVenues->count() - $i;
                break;
            }
            if ($this->reprogrammer($reservation, (int) $creneauId, $agentId) === null) {
                $rapport['faites']++;
            }
        }

        return $rapport;
    }

    /**
     * Les jours passes ou des familles non venues attendent encore d'etre
     * reprogrammees — pour qu'aucun jour ne soit perdu parce qu'on ne l'a
     * plus ouvert.
     *
     * @return Collection<int, object{jour: string, n: int}>
     */
    public function joursEnSouffrance(Carbon $sauf): Collection
    {
        $maintenant = Carbon::now();

        return $this->nonVenuesPassees($maintenant)
            ->whereDate('c.date', '!=', $sauf->toDateString())
            ->selectRaw('c.date AS jour, COUNT(*) AS n')
            ->groupBy('c.date')
            ->orderByDesc('c.date')
            ->toBase()
            ->get()
            ->map(fn ($l) => (object) ['jour' => Carbon::parse($l->jour)->toDateString(), 'n' => (int) $l->n]);
    }

    private function nonVenuesPassees(Carbon $maintenant): Builder
    {
        return ESBTPRdvReservation::query()
            ->join('esbtp_rdv_creneaux as c', 'c.id', '=', 'esbtp_rdv_reservations.creneau_id')
            ->where('esbtp_rdv_reservations.statut', StatutReservationRdv::Confirmee->value)
            ->where(fn (Builder $q) => $q->whereDate('c.date', '<', $maintenant->toDateString())
                ->orWhere(fn (Builder $j) => $j->whereDate('c.date', $maintenant->toDateString())
                    ->where('c.heure_fin', '<=', $maintenant->format('H:i:s'))))
            ->where(fn (Builder $q) => $q
                ->whereHas('candidature', fn ($c) => $c->where('statut', ESBTPCandidature::STATUT_EN_ATTENTE))
                ->orWhereHas('demande', fn ($d) => $d->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)));
    }

    /**
     * Les non-venues de ce jour deja deplacees sur un autre jour. Le journal
     * garde chaque deplacement : une seconde absence ailleurs n'efface pas la
     * trace du premier jour.
     *
     * @return Collection<int, ESBTPRdvReservation>
     */
    private function reprogrammeesDepuis(Carbon $jour): Collection
    {
        return ESBTPRdvReprogrammation::query()
            ->where('non_venue', true)
            ->whereHas('creneauQuitte', fn ($q) => $q->whereDate('date', $jour->toDateString()))
            ->with(['reservation' => fn ($q) => $q->with(array_merge(['creneau'], ContactsFamilleRdv::chargements()))])
            ->orderBy('id')
            ->get()
            ->pluck('reservation')
            ->filter(fn (?ESBTPRdvReservation $r) => $r !== null && $r->creneau !== null
                && $r->creneau->date->toDateString() !== $jour->toDateString())
            ->unique('id')
            ->sortBy(fn (ESBTPRdvReservation $r) => $r->nomComplet())
            ->values();
    }

    private function dossierEnAttente(ESBTPRdvReservation $r): bool
    {
        $porteur = $r->candidature ?? $r->demande;

        return $porteur !== null && $porteur->statut === ($r->candidature
            ? ESBTPCandidature::STATUT_EN_ATTENTE
            : ESBTPReinscriptionDemande::STATUT_EN_ATTENTE);
    }

    /**
     * Relit la ligne sous verrou et refuse si elle a change de creneau entre-temps :
     * un autre guichet a pu la reprogrammer pendant qu'on cliquait.
     */
    private function transition(ESBTPRdvReservation $reservation, StatutReservationRdv $vers, ?int $agentId): ?string
    {
        return DB::transaction(function () use ($reservation, $vers, $agentId) {
            $r = ESBTPRdvReservation::query()->occupantes()->whereKey($reservation->id)->lockForUpdate()->first();
            if ($r === null) {
                return 'Ce rendez-vous a été annulé entre-temps.';
            }
            if ((int) $r->creneau_id !== (int) $reservation->creneau_id) {
                return 'Ce rendez-vous vient d\'être déplacé par un autre poste. La liste est rechargée.';
            }
            if ($r->statut === $vers) {
                return null;
            }

            $r->forceFill([
                'statut' => $vers,
                'accueilli_at' => $vers === StatutReservationRdv::Honoree ? now() : null,
                'accueilli_par' => $vers === StatutReservationRdv::Honoree ? $agentId : null,
            ])->save();

            return null;
        });
    }

    private function debut(ESBTPRdvCreneau $creneau): Carbon
    {
        return Carbon::parse($creneau->date->toDateString().' '.$creneau->heureDebutHi().':00');
    }
}
