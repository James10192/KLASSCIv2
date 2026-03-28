<?php

namespace App\Services\ESBTP;

use App\Models\ESBTPAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ESBTPAbsenceService
{
    /**
     * Calcule les détails des absences pour un étudiant
     *
     * @param int $etudiantId
     * @param int $classeId
     * @param string|null $dateDebut
     * @param string|null $dateFin
     * @return array
     */
    public function calculerDetailAbsences($etudiantId, $classeId, $dateDebut = null, $dateFin = null)
    {
        // Si les dates ne sont pas spécifiées, utiliser la période actuelle
        if (!$dateDebut) {
            $dateDebut = Carbon::now()->startOfMonth()->format('Y-m-d');
        }
        if (!$dateFin) {
            $dateFin = Carbon::now()->format('Y-m-d');
        }

        // Récupérer toutes les absences pour la période
        $query = ESBTPAttendance::where('etudiant_id', $etudiantId);

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date', [$dateDebut, $dateFin]);
        }

        $absences = $query->get();

        // Initialiser les compteurs
        $absencesJustifiees = 0;
        $absencesNonJustifiees = 0;
        $detailJustifiees = [];
        $detailNonJustifiees = [];

        // Calculer les heures d'absence
        foreach ($absences as $absence) {
            // Calculer la durée de l'absence en heures
            $heureDebut = Carbon::parse($absence->heure_debut);
            $heureFin = Carbon::parse($absence->heure_fin);
            $duree = $heureDebut->diffInHours($heureFin);

            $detail = [
                'date' => $absence->date,
                'duree' => $duree,
                'commentaire' => $absence->commentaire ?? ''
            ];

            if ($absence->statut === 'absent_excuse' || $absence->justified_at) {
                $absencesJustifiees += $duree;
                $detailJustifiees[] = $detail;
            } else {
                $absencesNonJustifiees += $duree;
                $detailNonJustifiees[] = $detail;
            }
        }

        return [
            'justifiees' => $absencesJustifiees,
            'non_justifiees' => $absencesNonJustifiees,
            'total' => $absencesJustifiees + $absencesNonJustifiees,
            'detail' => [
                'justifiees' => $detailJustifiees,
                'non_justifiees' => $detailNonJustifiees
            ]
        ];
    }

    /**
     * Calcule les absences par matière pour un étudiant (en heures)
     */
    public function calculerAbsencesParMatiere($etudiantId, $classeId, $dateDebut = null, $dateFin = null)
    {
        if (!$dateDebut) {
            $dateDebut = Carbon::now()->startOfYear()->format('Y-m-d');
        }
        if (!$dateFin) {
            $dateFin = Carbon::now()->format('Y-m-d');
        }

        $absences = ESBTPAttendance::where('etudiant_id', $etudiantId)
            ->whereNotNull('matiere_id')
            ->whereIn('statut', ['absent', 'absent_excuse'])
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->get();

        $parMatiere = [];
        $totalHeures = 0;

        foreach ($absences as $absence) {
            $matiereId = $absence->matiere_id;
            $heureDebut = Carbon::parse($absence->heure_debut);
            $heureFin = Carbon::parse($absence->heure_fin);
            $duree = max(1, $heureDebut->diffInHours($heureFin));

            if (!isset($parMatiere[$matiereId])) {
                $parMatiere[$matiereId] = [
                    'matiere_id' => $matiereId,
                    'total_heures' => 0,
                    'justifiees' => 0,
                    'non_justifiees' => 0,
                ];
            }

            $parMatiere[$matiereId]['total_heures'] += $duree;
            $totalHeures += $duree;

            if ($absence->statut === 'absent_excuse' || $absence->justified_at) {
                $parMatiere[$matiereId]['justifiees'] += $duree;
            } else {
                $parMatiere[$matiereId]['non_justifiees'] += $duree;
            }
        }

        return [
            'par_matiere' => $parMatiere,
            'total_heures' => $totalHeures,
        ];
    }
}
