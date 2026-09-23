<?php

namespace App\Services\RendezVous;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvCreneau;
use App\Services\Reinscription\PortailReinscriptionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CatalogueCreneaux
{
    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly PortailReinscriptionService $reinscriptions,
    ) {
    }

    /**
     * @return list<array{id: int, date: string, heure_debut: string, heure_fin: string, etat: string}>
     */
    public function publier(): array
    {
        return $this->creneauxOuverts()->map(function (ESBTPRdvCreneau $creneau) {
            $prises = (int) ($creneau->prises ?? 0);

            return [
                'id' => (int) $creneau->id,
                'date' => $creneau->date->toDateString(),
                'heure_debut' => $creneau->heureDebutHi(),
                'heure_fin' => $creneau->heureFinHi(),
                'etat' => $prises >= $creneau->capacite ? 'complet' : 'disponible',
            ];
        })->values()->all();
    }

    /**
     * @return array<int, int>
     */
    public function placesLibres(): array
    {
        $restantes = [];
        foreach ($this->creneauxOuverts() as $creneau) {
            $libre = (int) $creneau->capacite - (int) ($creneau->prises ?? 0);
            if ($libre > 0 && ! $creneau->aCommence()) {
                $restantes[(int) $creneau->id] = $libre;
            }
        }

        return $restantes;
    }

    public function anneeDesCreneaux(): ?ESBTPAnneeUniversitaire
    {
        return $this->reinscriptions->anneeCible();
    }

    /**
     * @return Collection<int, ESBTPRdvCreneau>
     */
    private function creneauxOuverts(): Collection
    {
        try {
            $regle = $this->reglages->pourGeneration();
        } catch (ReglagesRdvIncomplets) {
            return collect();
        }

        $annee = $this->reinscriptions->anneeCible();
        if ($annee === null) {
            return collect();
        }

        $debut = Carbon::today();
        if ($regle->plancher->gt($debut)) {
            $debut = $regle->plancher->copy();
        }

        return ESBTPRdvCreneau::query()
            ->where('annee_universitaire_id', $annee->id)
            ->where('ouvert', true)
            ->whereDate('date', '>=', $debut->toDateString())
            ->whereDate('date', '<=', $regle->fermeture->toDateString())
            ->withCount(['reservations as prises' => fn ($q) => $q->occupantes()])
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get();
    }
}
