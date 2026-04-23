<?php

namespace App\Services\ESBTP;

use App\Models\ESBTPAttendance;
use App\Support\Attendance\ManualHoursSnapshot;
use Carbon\Carbon;

class ESBTPAbsenceService
{
    public function __construct(
        protected ManualHoursResolver $resolver,
    ) {
    }

    /**
     * Calcule les détails des absences pour un étudiant.
     *
     * Si $anneeUniversitaireId et $periode sont fournis, la saisie manuelle par matière
     * (table esbtp_attendance_manual_hours) devient prioritaire sur le calcul session-based
     * pour les matières concernées. La ligne "globale" (matiere_id NULL) est également
     * sommée au total étudiant (mais n'est jamais ventilée par matière, cf.
     * `calculerAbsencesParMatiere`).
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

        $snapshot = $this->snapshot($etudiantId, $anneeUniversitaireId, $periode);
        $manualByMatiere = $snapshot->perMatiere;
        $manualMatiereIds = $snapshot->matiereIdsWithManual();

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

        if ($snapshot->global !== null) {
            $gJust = (float) $snapshot->global->heures_absence_justifiees;
            $gNonJust = (float) $snapshot->global->heures_absence_non_justifiees;
            $absencesJustifiees += $gJust;
            $absencesNonJustifiees += $gNonJust;

            if ($gJust > 0) {
                $detailJustifiees[] = [
                    'date' => null,
                    'duree' => $gJust,
                    'commentaire' => $snapshot->global->notes ?? 'Saisie globale (sans matière)',
                    'source' => 'manual_global',
                    'matiere_id' => null,
                ];
            }
            if ($gNonJust > 0) {
                $detailNonJustifiees[] = [
                    'date' => null,
                    'duree' => $gNonJust,
                    'commentaire' => $snapshot->global->notes ?? 'Saisie globale (sans matière)',
                    'source' => 'manual_global',
                    'matiere_id' => null,
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
            'has_global' => $snapshot->global !== null,
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

        $snapshot = $this->snapshot($etudiantId, $anneeUniversitaireId, $periode);
        $manualByMatiere = $snapshot->perMatiere;
        $manualMatiereIds = $snapshot->matiereIdsWithManual();

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
            'has_global' => $snapshot->global !== null,
            'global' => $snapshot->global ? [
                'justifiees' => (float) $snapshot->global->heures_absence_justifiees,
                'non_justifiees' => (float) $snapshot->global->heures_absence_non_justifiees,
                'presence' => (float) $snapshot->global->heures_presence,
                'notes' => $snapshot->global->notes,
            ] : null,
        ];
    }

    /**
     * Wrapper autour du resolver qui absorbe la normalisation de période
     * (les appels historiques passent parfois '1'/'S1' au lieu de
     * 'semestre1'). Retourne un snapshot vide si aucun contexte de
     * période n'est fourni.
     */
    private function snapshot($etudiantId, $anneeUniversitaireId, $periode): ManualHoursSnapshot
    {
        if (!$anneeUniversitaireId || !$periode) {
            return ManualHoursSnapshot::empty();
        }

        return $this->resolver->snapshot(
            (int) $etudiantId,
            (int) $anneeUniversitaireId,
            $this->normalizePeriode((string) $periode)
        );
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
