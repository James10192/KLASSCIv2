<?php

namespace App\Services\ESBTP;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPAttendanceManualHours;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ESBTPAbsenceService
{
    /**
     * Calcule les détails des absences pour un étudiant.
     *
     * Si $anneeUniversitaireId et $periode sont fournis, la saisie manuelle par matière
     * (table esbtp_attendance_manual_hours) devient prioritaire sur le calcul session-based
     * pour les matières concernées.
     */
    public function calculerDetailAbsences(
        $etudiantId,
        $classeId,
        $dateDebut = null,
        $dateFin = null,
        $anneeUniversitaireId = null,
        $periode = null
    ) {
        if (!$dateDebut) {
            $dateDebut = Carbon::now()->startOfMonth()->format('Y-m-d');
        }
        if (!$dateFin) {
            $dateFin = Carbon::now()->format('Y-m-d');
        }

        $manualByMatiere = $this->loadManualByMatiere($etudiantId, $anneeUniversitaireId, $periode);
        $manualMatiereIds = $manualByMatiere->keys()->all();

        $sessionsQuery = ESBTPAttendance::where('etudiant_id', $etudiantId)
            ->whereBetween('date', [$dateDebut, $dateFin]);

        if (!empty($manualMatiereIds)) {
            $sessionsQuery->where(function ($q) use ($manualMatiereIds) {
                $q->whereNull('matiere_id')
                    ->orWhereNotIn('matiere_id', $manualMatiereIds);
            });
        }

        $absences = $sessionsQuery->get();

        $absencesJustifiees = 0.0;
        $absencesNonJustifiees = 0.0;
        $detailJustifiees = [];
        $detailNonJustifiees = [];

        foreach ($absences as $absence) {
            if (!$absence->heure_debut || !$absence->heure_fin) {
                continue;
            }

            $heureDebut = Carbon::parse($absence->heure_debut);
            $heureFin = Carbon::parse($absence->heure_fin);
            $duree = $heureDebut->diffInHours($heureFin);

            $detail = [
                'date' => $absence->date,
                'duree' => $duree,
                'commentaire' => $absence->commentaire ?? '',
                'source' => 'sessions',
            ];

            if ($absence->statut === 'absent_excuse' || $absence->justified_at) {
                $absencesJustifiees += $duree;
                $detailJustifiees[] = $detail;
            } elseif ($absence->statut === 'absent') {
                $absencesNonJustifiees += $duree;
                $detailNonJustifiees[] = $detail;
            }
        }

        foreach ($manualByMatiere as $row) {
            $absencesJustifiees += (float) $row->heures_absence_justifiees;
            $absencesNonJustifiees += (float) $row->heures_absence_non_justifiees;

            if ((float) $row->heures_absence_justifiees > 0) {
                $detailJustifiees[] = [
                    'date' => null,
                    'duree' => (float) $row->heures_absence_justifiees,
                    'commentaire' => $row->notes ?? 'Saisie manuelle ('.optional($row->matiere)->name.')',
                    'source' => 'manual',
                    'matiere_id' => $row->matiere_id,
                ];
            }
            if ((float) $row->heures_absence_non_justifiees > 0) {
                $detailNonJustifiees[] = [
                    'date' => null,
                    'duree' => (float) $row->heures_absence_non_justifiees,
                    'commentaire' => $row->notes ?? 'Saisie manuelle ('.optional($row->matiere)->name.')',
                    'source' => 'manual',
                    'matiere_id' => $row->matiere_id,
                ];
            }
        }

        return [
            'justifiees' => $absencesJustifiees,
            'non_justifiees' => $absencesNonJustifiees,
            'total' => $absencesJustifiees + $absencesNonJustifiees,
            'detail' => [
                'justifiees' => $detailJustifiees,
                'non_justifiees' => $detailNonJustifiees,
            ],
            'manual_matieres' => $manualMatiereIds,
        ];
    }

    /**
     * Calcule les absences par matière pour un étudiant (en heures).
     *
     * Si $anneeUniversitaireId et $periode sont fournis, la saisie manuelle devient prioritaire
     * par matière. Chaque entrée du tableau retourné contient 'source' = 'manual' | 'sessions'.
     */
    public function calculerAbsencesParMatiere(
        $etudiantId,
        $classeId,
        $dateDebut = null,
        $dateFin = null,
        $anneeUniversitaireId = null,
        $periode = null
    ) {
        if (!$dateDebut) {
            $dateDebut = Carbon::now()->startOfYear()->format('Y-m-d');
        }
        if (!$dateFin) {
            $dateFin = Carbon::now()->format('Y-m-d');
        }

        $manualByMatiere = $this->loadManualByMatiere($etudiantId, $anneeUniversitaireId, $periode);
        $manualMatiereIds = $manualByMatiere->keys()->all();

        $sessionsQuery = ESBTPAttendance::where('etudiant_id', $etudiantId)
            ->whereNotNull('matiere_id')
            ->whereIn('statut', ['absent', 'absent_excuse'])
            ->whereBetween('date', [$dateDebut, $dateFin]);

        if (!empty($manualMatiereIds)) {
            $sessionsQuery->whereNotIn('matiere_id', $manualMatiereIds);
        }

        $absences = $sessionsQuery->get();

        $parMatiere = [];
        $totalHeures = 0.0;

        foreach ($absences as $absence) {
            $matiereId = $absence->matiere_id;
            if (!$absence->heure_debut || !$absence->heure_fin) {
                continue;
            }
            $heureDebut = Carbon::parse($absence->heure_debut);
            $heureFin = Carbon::parse($absence->heure_fin);
            $duree = max(1, $heureDebut->diffInHours($heureFin));

            if (!isset($parMatiere[$matiereId])) {
                $parMatiere[$matiereId] = [
                    'matiere_id' => $matiereId,
                    'total_heures' => 0,
                    'justifiees' => 0,
                    'non_justifiees' => 0,
                    'source' => 'sessions',
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

        foreach ($manualByMatiere as $matiereId => $row) {
            $justif = (float) $row->heures_absence_justifiees;
            $nonJustif = (float) $row->heures_absence_non_justifiees;
            $total = $justif + $nonJustif;

            $parMatiere[$matiereId] = [
                'matiere_id' => $matiereId,
                'total_heures' => $total,
                'justifiees' => $justif,
                'non_justifiees' => $nonJustif,
                'source' => 'manual',
                'heures_presence' => (float) $row->heures_presence,
                'notes' => $row->notes,
            ];
            $totalHeures += $total;
        }

        return [
            'par_matiere' => $parMatiere,
            'total_heures' => $totalHeures,
            'manual_matieres' => $manualMatiereIds,
        ];
    }

    /**
     * Charge les saisies manuelles par matière pour un étudiant/période.
     * Retourne une collection indexée par matiere_id (vide si pas de contexte).
     */
    private function loadManualByMatiere($etudiantId, $anneeUniversitaireId, $periode): Collection
    {
        if (!$anneeUniversitaireId || !$periode) {
            return collect();
        }

        $normalizedPeriode = $this->normalizePeriode((string) $periode);

        return ESBTPAttendanceManualHours::with('matiere:id,name')
            ->where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('periode', $normalizedPeriode)
            ->get()
            ->keyBy('matiere_id');
    }

    /**
     * Normalise les variantes de période ('1', '2', 'S1', 'S2') en clé canonique
     * compatible avec esbtp_attendance_manual_hours.periode.
     */
    private function normalizePeriode(string $periode): string
    {
        $periode = trim($periode);

        if ($periode === '1' || strcasecmp($periode, 'S1') === 0) {
            return 'semestre1';
        }
        if ($periode === '2' || strcasecmp($periode, 'S2') === 0) {
            return 'semestre2';
        }

        return $periode ?: 'semestre1';
    }
}
