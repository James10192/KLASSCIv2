<?php

namespace App\Services\RendezVous;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Models\ESBTPRdvCreneau;
use App\Services\Reinscription\PortailReinscriptionService;
use Carbon\Carbon;

class CatalogueCreneaux
{
    public function __construct(private readonly RendezVousReglages $reglages)
    {
    }

    /**
     * @return list<array{id: int, date: string, heure_debut: string, heure_fin: string, etat: string}>
     */
    public function publier(): array
    {
        try {
            $regle = $this->reglages->pourGeneration();
        } catch (ReglagesRdvIncomplets) {
            return [];
        }

        $annee = app(PortailReinscriptionService::class)->anneeCible();
        if ($annee === null) {
            return [];
        }

        $debut = Carbon::today();
        if ($regle->plancher->gt($debut)) {
            $debut = $regle->plancher->copy();
        }

        $creneaux = ESBTPRdvCreneau::query()
            ->where('annee_universitaire_id', $annee->id)
            ->where('ouvert', true)
            ->whereDate('date', '>=', $debut->toDateString())
            ->whereDate('date', '<=', $regle->fermeture->toDateString())
            ->withCount(['reservations as prises' => fn ($q) => $q->occupantes()])
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get();

        return $creneaux->map(function (ESBTPRdvCreneau $creneau) {
            $prises = (int) ($creneau->prises ?? 0);

            return [
                'id' => (int) $creneau->id,
                'date' => $creneau->date->toDateString(),
                'heure_debut' => $creneau->heureDebutHi(),
                'heure_fin' => $creneau->heureFinHi(),
                'etat' => $prises >= $creneau->capacite ? 'complet' : 'disponible',
            ];
        })->all();
    }
}
