<?php

namespace App\Services\RendezVous;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Services\Reinscription\PortailReinscriptionService;
use Carbon\Carbon;

/**
 * Ce que montre l'ecran des rendez-vous, pour une semaine.
 *
 * L'ancien ecran ne montrait que des creneaux et leur capacite : pas une
 * reservation, pas un courriel. Sur esbtp-abidjan, 44 creneaux complets
 * s'affichaient exactement comme des creneaux vides. Chaque creneau porte
 * desormais ses reservations et l'etat de leur convocation.
 */
class TableauRendezVous
{
    public function __construct(
        private readonly EtatChaineRdv $etat,
        private readonly RendezVousReglages $reglages,
        private readonly CatalogueCreneaux $catalogue,
    ) {
    }

    /** @return array<string, mixed> */
    public function pourSemaine(?string $debutBrut): array
    {
        $jour = PortailReinscriptionService::interpreterDateIso((string) $debutBrut) ?? Carbon::today();
        $debut = $jour->copy()->startOfWeek(Carbon::MONDAY);
        $fin = $debut->copy()->addDays(6);

        $creneaux = ESBTPRdvCreneau::query()
            ->whereDate('date', '>=', $debut->toDateString())
            ->whereDate('date', '<=', $fin->toDateString())
            ->with(['reservations' => fn ($q) => $q->occupantes()
                ->with(['prevenuePar:id,name', 'candidature:id,statut', 'demande:id,statut'])->orderBy('nom')])
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get()
            // Le creneau et le dossier deja en main : FamillesAPrevenirRdv::concerne()
            // ne relance aucune requete par ligne.
            ->each(fn (ESBTPRdvCreneau $c) => $c->reservations->each->setRelation('creneau', $c))
            ->groupBy(fn (ESBTPRdvCreneau $c) => $c->date->toDateString());

        $jours = [];
        for ($curseur = $debut->copy(); $curseur->lte($fin); $curseur->addDay()) {
            $duJour = $creneaux->get($curseur->toDateString(), collect());
            $jours[] = [
                'date' => $curseur->toDateString(),
                'libelle' => ucfirst($curseur->translatedFormat('l j F')),
                'aujourdhui' => $curseur->isToday(),
                'passe' => $curseur->lt(Carbon::today()),
                'creneaux' => $duJour,
                'places' => (int) $duJour->sum('capacite'),
                'prises' => (int) $duJour->sum(fn (ESBTPRdvCreneau $c) => $c->reservations->count()),
            ];
        }

        return [
            'debut' => $debut,
            'fin' => $fin,
            'semainePrecedente' => $debut->copy()->subWeek()->toDateString(),
            'semaineSuivante' => $debut->copy()->addWeek()->toDateString(),
            'jours' => $jours,
            'semaineVide' => $creneaux->isEmpty(),
            'prochainJour' => $this->prochainJourAvecCreneau(),
            'maillons' => $this->etat->maillons(),
            'convocations' => $this->etat->convocations(),
            'kpis' => $this->kpis(),
            'debit' => $this->reglages->debitJournalier(),
        ];
    }

    /**
     * Les places libres viennent du catalogue, le meme que lit le portail et le
     * placement : un second calcul ici ignorait l'annee cible, la fenetre et les
     * heures passees, et le bandeau annoncait des places que le bouton refusait.
     *
     * @return array{reservations: int, libres: int, creneaux: int}
     */
    private function kpis(): array
    {
        $libres = $this->catalogue->placesLibres();

        return [
            'reservations' => ESBTPRdvReservation::query()
                ->where('statut', StatutReservationRdv::Confirmee->value)
                ->whereHas('creneau', fn ($q) => $q->whereDate('date', '>=', Carbon::today()->toDateString()))
                ->count(),
            'libres' => array_sum($libres),
            'creneaux' => count($libres),
        ];
    }

    private function prochainJourAvecCreneau(): ?Carbon
    {
        $date = ESBTPRdvCreneau::query()
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->orderBy('date')
            ->value('date');

        return $date === null ? null : Carbon::parse($date);
    }
}
