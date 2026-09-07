<?php

namespace App\Services\RendezVous;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvCreneau;
use App\Services\Reinscription\PortailReinscriptionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateurCreneaux
{
    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly PortailReinscriptionService $portail,
    ) {
    }

    public function generer(?Carbon $maintenant = null): RapportGeneration
    {
        $regle = $this->reglages->pourGeneration();
        $maintenant = $maintenant?->copy() ?? Carbon::now();
        $annee = $this->anneeCible();

        $theoriques = self::theoriques($regle, $maintenant);
        $clesTheoriques = [];
        foreach ($theoriques as $slot) {
            $clesTheoriques[$slot['date'].'|'.$slot['heure_debut']] = $slot;
        }

        $crees = 0;
        $misAJour = 0;
        $fermes = 0;
        $conserves = 0;

        DB::transaction(function () use ($annee, $clesTheoriques, $regle, &$crees, &$misAJour, &$fermes, &$conserves) {
            foreach ($clesTheoriques as $slot) {
                $existant = ESBTPRdvCreneau::query()
                    ->where('annee_universitaire_id', $annee->id)
                    ->whereDate('date', $slot['date'])
                    ->whereTime('heure_debut', $slot['heure_debut'])
                    ->lockForUpdate()
                    ->first();

                if ($existant === null) {
                    ESBTPRdvCreneau::create([
                        'annee_universitaire_id' => $annee->id,
                        'date' => $slot['date'],
                        'heure_debut' => $slot['heure_debut'],
                        'heure_fin' => $slot['heure_fin'],
                        'capacite' => $regle->capacite,
                        'ouvert' => true,
                    ]);
                    $crees++;

                    continue;
                }

                if ($existant->estOccupe()) {
                    $conserves++;

                    continue;
                }

                $existant->update([
                    'heure_fin' => $slot['heure_fin'],
                    'capacite' => $regle->capacite,
                    'ouvert' => true,
                ]);
                $misAJour++;
            }

            $existants = ESBTPRdvCreneau::query()
                ->where('annee_universitaire_id', $annee->id)
                ->whereDate('date', '>=', $regle->plancher->toDateString())
                ->whereDate('date', '<=', $regle->fermeture->toDateString())
                ->lockForUpdate()
                ->get();

            foreach ($existants as $creneau) {
                $cle = $creneau->date->toDateString().'|'.$creneau->heureDebutHi();
                if (isset($clesTheoriques[$cle])) {
                    continue;
                }

                if ($creneau->estOccupe()) {
                    $conserves++;

                    continue;
                }

                if ($creneau->ouvert) {
                    $creneau->update(['ouvert' => false]);
                    $fermes++;
                }
            }
        });

        return new RapportGeneration($crees, $misAJour, $fermes, $conserves);
    }

    /**
     * @return list<array{date: string, heure_debut: string, heure_fin: string}>
     */
    public static function theoriques(CreneauRegle $regle, Carbon $maintenant): array
    {
        $slots = [];
        $jour = $regle->plancher->copy()->startOfDay();
        $fin = $regle->fermeture->copy()->startOfDay();
        $aujourdHui = $maintenant->copy()->startOfDay();

        while ($jour->lte($fin)) {
            if (in_array((int) $jour->dayOfWeekIso, $regle->joursOuverts, true)) {
                foreach (self::horairesDuJour($regle) as [$debut, $finHeure]) {
                    if ($jour->lt($aujourdHui)) {
                        continue;
                    }
                    if ($jour->equalTo($aujourdHui)) {
                        $debutDuSlot = $jour->copy()->setTimeFromTimeString($debut.':00');
                        if ($debutDuSlot->lte($maintenant)) {
                            continue;
                        }
                    }

                    $slots[] = [
                        'date' => $jour->toDateString(),
                        'heure_debut' => $debut,
                        'heure_fin' => $finHeure,
                    ];
                }
            }

            $jour->addDay();
        }

        return $slots;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function horairesDuJour(CreneauRegle $regle): array
    {
        $horaires = [];
        $curseur = self::minutes($regle->heureDebut);
        $fin = self::minutes($regle->heureFin);
        $pauseDebut = $regle->pauseDebut !== null ? self::minutes($regle->pauseDebut) : null;
        $pauseFin = $regle->pauseFin !== null ? self::minutes($regle->pauseFin) : null;

        while ($curseur + $regle->dureeMinutes <= $fin) {
            $finSlot = $curseur + $regle->dureeMinutes;
            $chevauchePause = $pauseDebut !== null && $pauseFin !== null
                && $curseur < $pauseFin
                && $finSlot > $pauseDebut;

            if (! $chevauchePause) {
                $horaires[] = [self::formatMinutes($curseur), self::formatMinutes($finSlot)];
            }

            $curseur += $regle->dureeMinutes;
        }

        return $horaires;
    }

    private function anneeCible(): ESBTPAnneeUniversitaire
    {
        $annee = $this->portail->anneeCible();
        if ($annee === null) {
            throw new ReglagesRdvIncomplets([PortailReinscriptionService::REGLAGE_ANNEE_CIBLE]);
        }

        return $annee;
    }

    private static function minutes(string $heure): int
    {
        [$h, $m] = array_map('intval', explode(':', $heure));

        return ($h * 60) + $m;
    }

    private static function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
