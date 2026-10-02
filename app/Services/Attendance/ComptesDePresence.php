<?php

namespace App\Services\Attendance;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPSeanceCours;

class ComptesDePresence
{
    /**
     * Présences finales de l'année par classe (celle de l'emploi du temps de la
     * séance) et par statut, en deux requêtes quel que soit le nombre de classes.
     * « late » est compté comme « retard ».
     *
     * @return array<int, array<string, int>> classe_id => [statut => nombre]
     */
    public function comptesParClasse(ESBTPAnneeUniversitaire $annee): array
    {
        $parSeance = ESBTPAttendance::finalOnly()
            ->where('annee_universitaire_id', $annee->id)
            ->toBase()
            ->selectRaw('seance_cours_id, statut, COUNT(*) as n')
            ->groupBy('seance_cours_id', 'statut')
            ->get();

        // Classe de chaque séance par son emploi du temps (même un emploi du temps
        // supprimé, comme la relation seanceCours.emploiTemps ; pas une séance supprimée).
        $classeDeLaSeance = ESBTPSeanceCours::query()
            ->whereIn('esbtp_seance_cours.id', ESBTPAttendance::query()
                ->select('seance_cours_id')
                ->where('annee_universitaire_id', $annee->id))
            ->join('esbtp_emploi_temps', 'esbtp_emploi_temps.id', '=', 'esbtp_seance_cours.emploi_temps_id')
            ->pluck('esbtp_emploi_temps.classe_id', 'esbtp_seance_cours.id');

        $comptes = [];
        foreach ($parSeance as $ligne) {
            $classeId = $classeDeLaSeance[$ligne->seance_cours_id] ?? null;
            if ($classeId === null) {
                continue;
            }
            $statut = $ligne->statut === 'late' ? 'retard' : $ligne->statut;
            $comptes[$classeId][$statut] = ($comptes[$classeId][$statut] ?? 0) + (int) $ligne->n;
        }

        return $comptes;
    }
}
