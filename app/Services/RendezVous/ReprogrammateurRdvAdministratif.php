<?php

namespace App\Services\RendezVous;

use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReprogrammation;
use App\Models\ESBTPRdvReservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reprogramme en bloc les rendez-vous d'un jour futur supprimé du planning.
 *
 * Cas métier : un vendredi (ou tout autre jour) ne doit finalement plus recevoir
 * de familles alors que des réservations existent déjà. La simulation choisit les
 * prochains créneaux conformes aux réglages courants, strictement après le jour
 * quitté. L'exécution ferme les créneaux source, déplace tout le lot sous verrou,
 * journalise chaque déplacement et replannifie les convocations « deplace ».
 */
class ReprogrammateurRdvAdministratif
{
    public function __construct(
        private readonly CatalogueCreneaux $catalogue,
        private readonly RendezVousReglages $reglages,
        private readonly ReservateurRdv $reservateur,
        private readonly FileConvocationsRdv $convocations,
    ) {
    }

    /**
     * @return array{
     *   date: string,
     *   reservations: int,
     *   creneaux_source: list<int>,
     *   creneaux_source_ouverts: int,
     *   assignations: list<array{reservation_id:int,source_id:int,cible_id:int}>,
     *   cibles: list<array{id:int,date:string,heure:string,affectees:int}>,
     *   sans_place: int
     * }
     */
    public function simuler(Carbon $jour): array
    {
        $jour = $jour->copy()->startOfDay();
        $reservations = $this->reservationsDuJour($jour)->get();
        $sources = $reservations->pluck('creneau')->filter()->unique('id')->values();

        $regle = $this->reglages->pourGeneration();
        $places = $this->catalogue->placesLibres();
        $cibles = ESBTPRdvCreneau::query()
            ->whereKey(array_keys($places))
            ->whereDate('date', '>', $jour->toDateString())
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get()
            ->filter(fn (ESBTPRdvCreneau $c) => in_array((int) $c->date->dayOfWeekIso, $regle->joursOuverts, true))
            ->values();

        $restantes = [];
        foreach ($cibles as $cible) {
            $restantes[(int) $cible->id] = (int) ($places[(int) $cible->id] ?? 0);
        }

        $assignations = [];
        $affectees = [];
        $sansPlace = 0;

        foreach ($reservations as $reservation) {
            $cibleId = null;
            foreach ($cibles as $cible) {
                $id = (int) $cible->id;
                if (($restantes[$id] ?? 0) > 0) {
                    $cibleId = $id;
                    break;
                }
            }

            if ($cibleId === null) {
                $sansPlace++;
                continue;
            }

            $restantes[$cibleId]--;
            $affectees[$cibleId] = ($affectees[$cibleId] ?? 0) + 1;
            $assignations[] = [
                'reservation_id' => (int) $reservation->id,
                'source_id' => (int) $reservation->creneau_id,
                'cible_id' => $cibleId,
            ];
        }

        return [
            'date' => $jour->toDateString(),
            'reservations' => $reservations->count(),
            'creneaux_source' => $sources->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'creneaux_source_ouverts' => $sources->where('ouvert', true)->count(),
            'assignations' => $assignations,
            'cibles' => $cibles
                ->filter(fn (ESBTPRdvCreneau $c) => ($affectees[(int) $c->id] ?? 0) > 0)
                ->map(fn (ESBTPRdvCreneau $c) => [
                    'id' => (int) $c->id,
                    'date' => $c->date->toDateString(),
                    'heure' => $c->heureDebutHi().'–'.$c->heureFinHi(),
                    'affectees' => (int) ($affectees[(int) $c->id] ?? 0),
                ])->values()->all(),
            'sans_place' => $sansPlace,
        ];
    }

    /**
     * Exécute exactement une simulation prévalidée. Le lot est atomique : au
     * premier conflit, aucun rendez-vous ni fermeture de créneau n'est conservé.
     *
     * @param list<array{reservation_id:int,source_id:int,cible_id:int}> $assignations
     * @param list<int> $creneauxSource
     * @return array{faites:int,creneaux_fermes:int,convocations:int}
     */
    public function executer(array $assignations, array $creneauxSource, int $agentId): array
    {
        $faites = DB::transaction(function () use ($assignations, $creneauxSource, $agentId) {
            $sources = ESBTPRdvCreneau::query()
                ->whereKey($creneauxSource)
                ->lockForUpdate()
                ->get();
            if ($sources->count() !== count(array_unique(array_map('intval', $creneauxSource)))) {
                throw new RuntimeException('lot_modifie');
            }

            // Le verrou sur les créneaux source empêche une nouvelle réservation
            // publique de se glisser entre la seconde simulation et leur fermeture.
            $proposees = array_values(array_unique(array_map(
                fn (array $a) => (int) $a['reservation_id'],
                $assignations
            )));
            sort($proposees);
            $actuelles = ESBTPRdvReservation::query()
                ->whereIn('creneau_id', $creneauxSource)
                ->occupantes()
                ->dossierOuvert()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if ($actuelles !== $proposees) {
                throw new RuntimeException('lot_modifie');
            }

            ESBTPRdvCreneau::query()->whereKey($creneauxSource)->update(['ouvert' => false]);

            $n = 0;
            foreach ($assignations as $a) {
                $reservation = ESBTPRdvReservation::query()->find($a['reservation_id']);
                if ($reservation === null) {
                    throw new RuntimeException('introuvable');
                }

                $resultat = $this->reservateur->replacerAuGuichet(
                    $reservation,
                    (int) $a['cible_id'],
                    (int) $a['source_id'],
                    function (ESBTPRdvReservation $deplacee, int $quitteId) use ($agentId) {
                        ESBTPRdvReprogrammation::create([
                            'reservation_id' => $deplacee->id,
                            'creneau_quitte_id' => $quitteId,
                            'creneau_nouveau_id' => $deplacee->creneau_id,
                            'non_venue' => false,
                            'par' => $agentId,
                        ]);
                        $this->convocations->poser($deplacee, 'deplace');
                    }
                );

                if (! ($resultat['ok'] ?? false)) {
                    throw new RuntimeException((string) ($resultat['code'] ?? 'introuvable'));
                }
                $n++;
            }

            return $n;
        });

        if ($faites > 0) {
            $this->convocations->envoyerUnPaquetApres();
        }

        return [
            'faites' => $faites,
            'creneaux_fermes' => count(array_unique(array_map('intval', $creneauxSource))),
            'convocations' => $faites,
        ];
    }

    private function reservationsDuJour(Carbon $jour): Builder
    {
        $annee = $this->catalogue->anneeDesCreneaux()?->id ?? 0;

        return ESBTPRdvReservation::query()
            ->select('esbtp_rdv_reservations.*')
            ->join('esbtp_rdv_creneaux as c', 'c.id', '=', 'esbtp_rdv_reservations.creneau_id')
            ->where('c.annee_universitaire_id', $annee)
            ->whereDate('c.date', $jour->toDateString())
            ->occupantes()
            ->dossierOuvert()
            ->with('creneau')
            ->orderBy('c.heure_debut')
            ->orderBy('esbtp_rdv_reservations.id');
    }
}
