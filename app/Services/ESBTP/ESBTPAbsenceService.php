<?php

namespace App\Services\ESBTP;

use App\Enums\JustificationStatus;
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

        // Annuel : agréger les deux semestres (chacun avec sa règle d'écrasement),
        // en ne comptant les séances réelles qu'UNE seule fois sur l'année.
        if ($anneeUniversitaireId && $periode !== null
            && $this->normalizePeriode((string) $periode) === 'annuel') {
            return $this->aggregateAnnualAbsences((int) $etudiantId, (int) $anneeUniversitaireId, $dateDebut, $dateFin);
        }

        $snapshot = $this->snapshot($etudiantId, $anneeUniversitaireId, $periode);

        // PRIORITÉ 1 — Saisie GLOBALE par semestre : c'est le total autoritaire du
        // semestre. Elle ÉCRASE les séances réelles ET la saisie manuelle par matière
        // (on n'additionne pas — sinon le chiffre n'aurait aucun sens : le global EST
        // le total des heures d'absence du semestre).
        if ($snapshot->global !== null) {
            return $this->buildGlobalResult($snapshot);
        }

        // PRIORITÉ 2 — Saisie manuelle PAR MATIÈRE : elle ÉCRASE les séances réelles de
        // sa matière. Les autres matières restent comptées via les séances réelles.
        return $this->buildSessionsWithPerMatiere((int) $etudiantId, $dateDebut, $dateFin, $snapshot);
    }

    /**
     * Résultat lorsqu'une saisie globale par semestre est présente : elle écrase
     * séances + manuel par matière et devient l'unique total du semestre.
     */
    private function buildGlobalResult(ManualHoursSnapshot $snapshot): array
    {
        $gJust = (float) $snapshot->global->heures_absence_justifiees;
        $gNonJust = (float) $snapshot->global->heures_absence_non_justifiees;

        $detailJust = [];
        $detailNon = [];
        $commentaire = $snapshot->global->notes ?? 'Saisie globale (sans matière)';
        if ($gJust > 0) {
            $detailJust[] = ['date' => null, 'duree' => $gJust, 'commentaire' => $commentaire, 'source' => 'manual_global', 'matiere_id' => null];
        }
        if ($gNonJust > 0) {
            $detailNon[] = ['date' => null, 'duree' => $gNonJust, 'commentaire' => $commentaire, 'source' => 'manual_global', 'matiere_id' => null];
        }

        return [
            'justifiees' => $gJust,
            'non_justifiees' => $gNonJust,
            'total' => $gJust + $gNonJust,
            'detail' => ['justifiees' => $detailJust, 'non_justifiees' => $detailNon],
            'manual_matieres' => $snapshot->matiereIdsWithManual(),
            'has_global' => true,
        ];
    }

    /**
     * Résultat séances réelles + manuel par matière : le manuel par matière écrase
     * les séances de SA matière (exclusion dans la requête), les autres matières
     * restent comptées via les séances.
     */
    private function buildSessionsWithPerMatiere(int $etudiantId, $dateDebut, $dateFin, ManualHoursSnapshot $snapshot): array
    {
        $manualByMatiere = $snapshot->perMatiere;
        $manualMatiereIds = $snapshot->matiereIdsWithManual();

        [$sJust, $sNon, $sDetailJust, $sDetailNon] = $this->sumSessions($etudiantId, $dateDebut, $dateFin, $manualMatiereIds);

        $absencesJustifiees = $sJust;
        $absencesNonJustifiees = $sNon;
        $detailJustifiees = $sDetailJust;
        $detailNonJustifiees = $sDetailNon;

        foreach ($manualByMatiere as $row) {
            $absencesJustifiees += (float) $row->heures_absence_justifiees;
            $absencesNonJustifiees += (float) $row->heures_absence_non_justifiees;

            $commentaire = $row->notes ?? 'Saisie manuelle ('.optional($row->matiere)->name.')';
            if ((float) $row->heures_absence_justifiees > 0) {
                $detailJustifiees[] = ['date' => null, 'duree' => (float) $row->heures_absence_justifiees, 'commentaire' => $commentaire, 'source' => 'manual', 'matiere_id' => $row->matiere_id];
            }
            if ((float) $row->heures_absence_non_justifiees > 0) {
                $detailNonJustifiees[] = ['date' => null, 'duree' => (float) $row->heures_absence_non_justifiees, 'commentaire' => $commentaire, 'source' => 'manual', 'matiere_id' => $row->matiere_id];
            }
        }

        return [
            'justifiees' => $absencesJustifiees,
            'non_justifiees' => $absencesNonJustifiees,
            'total' => $absencesJustifiees + $absencesNonJustifiees,
            'detail' => ['justifiees' => $detailJustifiees, 'non_justifiees' => $detailNonJustifiees],
            'manual_matieres' => $manualMatiereIds,
            'has_global' => false,
        ];
    }

    /**
     * Somme les séances réelles (esbtp_attendances) sur la fenêtre de dates, en
     * excluant les matières couvertes par une saisie manuelle (celles-ci sont écrasées).
     *
     * @return array{0: float, 1: float, 2: array, 3: array} [justifiees, non_justifiees, detailJust, detailNon]
     */
    private function sumSessions(int $etudiantId, $dateDebut, $dateFin, array $excludeMatiereIds): array
    {
        $sessionsQuery = ESBTPAttendance::where('etudiant_id', $etudiantId)
            ->whereBetween('date', [$dateDebut, $dateFin]);

        if (!empty($excludeMatiereIds)) {
            $sessionsQuery->where(function ($q) use ($excludeMatiereIds) {
                $q->whereNull('matiere_id')
                    ->orWhereNotIn('matiere_id', $excludeMatiereIds);
            });
        }

        $justifiees = 0.0;
        $nonJustifiees = 0.0;
        $detailJust = [];
        $detailNon = [];

        foreach ($sessionsQuery->get() as $absence) {
            if (!$absence->heure_debut || !$absence->heure_fin) {
                continue;
            }

            $duree = $this->durationInHours(Carbon::parse($absence->heure_debut), Carbon::parse($absence->heure_fin));
            $detail = ['date' => $absence->date, 'duree' => $duree, 'commentaire' => $absence->commentaire ?? '', 'source' => 'sessions'];

            if ($this->isApprovedOrExcused($absence)) {
                $justifiees += $duree;
                $detailJust[] = $detail;
            } elseif ($absence->statut === 'absent') {
                $nonJustifiees += $duree;
                $detailNon[] = $detail;
            }
        }

        return [$justifiees, $nonJustifiees, $detailJust, $detailNon];
    }

    /**
     * Total annuel = total semestre 1 + total semestre 2, chacun calculé avec la
     * règle d'écrasement (global > par matière > séances). Les séances réelles sont
     * comptées UNE SEULE fois sur l'année (elles ne sont pas datées par semestre ici).
     *
     * Règle sur les séances quand un global existe : une saisie globale est le total
     * autoritaire de SON semestre → elle remplace les séances. Comme les séances ne
     * sont pas ventilées par semestre, dès qu'un semestre a un global on ne rajoute
     * pas les séances (le global couvre l'assiduité réelle).
     */
    private function aggregateAnnualAbsences(int $etudiantId, int $anneeId, $dateDebut, $dateFin): array
    {
        $s1 = $this->resolver->snapshot($etudiantId, $anneeId, 'semestre1');
        $s2 = $this->resolver->snapshot($etudiantId, $anneeId, 'semestre2');

        $justifiees = 0.0;
        $nonJustifiees = 0.0;
        $detailJust = [];
        $detailNon = [];
        $handledMatiereIds = array_values(array_unique(array_merge($s1->matiereIdsWithManual(), $s2->matiereIdsWithManual())));
        $anyGlobal = ($s1->global !== null) || ($s2->global !== null);

        foreach ([$s1, $s2] as $snap) {
            if ($snap->global !== null) {
                // Global : total autoritaire du semestre (écrase séances + par matière).
                $gJust = (float) $snap->global->heures_absence_justifiees;
                $gNon = (float) $snap->global->heures_absence_non_justifiees;
                $justifiees += $gJust;
                $nonJustifiees += $gNon;
                $commentaire = $snap->global->notes ?? 'Saisie globale (sans matière)';
                if ($gJust > 0) {
                    $detailJust[] = ['date' => null, 'duree' => $gJust, 'commentaire' => $commentaire, 'source' => 'manual_global', 'matiere_id' => null];
                }
                if ($gNon > 0) {
                    $detailNon[] = ['date' => null, 'duree' => $gNon, 'commentaire' => $commentaire, 'source' => 'manual_global', 'matiere_id' => null];
                }
                continue;
            }

            // Pas de global : contributions manuelles par matière du semestre.
            foreach ($snap->perMatiere as $row) {
                $justifiees += (float) $row->heures_absence_justifiees;
                $nonJustifiees += (float) $row->heures_absence_non_justifiees;
                $commentaire = $row->notes ?? 'Saisie manuelle ('.optional($row->matiere)->name.')';
                if ((float) $row->heures_absence_justifiees > 0) {
                    $detailJust[] = ['date' => null, 'duree' => (float) $row->heures_absence_justifiees, 'commentaire' => $commentaire, 'source' => 'manual', 'matiere_id' => $row->matiere_id];
                }
                if ((float) $row->heures_absence_non_justifiees > 0) {
                    $detailNon[] = ['date' => null, 'duree' => (float) $row->heures_absence_non_justifiees, 'commentaire' => $commentaire, 'source' => 'manual', 'matiere_id' => $row->matiere_id];
                }
            }
        }

        // Séances réelles : une seule fois sur l'année, pour les matières sans saisie
        // manuelle, et seulement si aucun semestre n'a de saisie globale.
        if (!$anyGlobal) {
            [$sJust, $sNon, $sDetailJust, $sDetailNon] = $this->sumSessions($etudiantId, $dateDebut, $dateFin, $handledMatiereIds);
            $justifiees += $sJust;
            $nonJustifiees += $sNon;
            $detailJust = array_merge($detailJust, $sDetailJust);
            $detailNon = array_merge($detailNon, $sDetailNon);
        }

        return [
            'justifiees' => $justifiees,
            'non_justifiees' => $nonJustifiees,
            'total' => $justifiees + $nonJustifiees,
            'detail' => ['justifiees' => $detailJust, 'non_justifiees' => $detailNon],
            'manual_matieres' => $handledMatiereIds,
            'has_global' => $anyGlobal,
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
            ->whereIn('statut', ['absent', 'excuse', 'absent_excuse'])
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
            $duree = $this->durationInHours($heureDebut, $heureFin);

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

            if ($this->isApprovedOrExcused($absence)) {
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

    private function isApprovedOrExcused(ESBTPAttendance $absence): bool
    {
        return $absence->justification_status === JustificationStatus::APPROVED
            || in_array($absence->statut, ['excuse', 'absent_excuse'], true);
    }

    private function durationInHours(Carbon $start, Carbon $end): float
    {
        return round($start->diffInMinutes($end) / 60, 2);
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

        $normalized = $this->normalizePeriode((string) $periode);

        // Un bulletin annuel doit agréger les saisies manuelles des deux semestres :
        // sinon `periode = 'annuel'` seul rate les heures stockées en semestre1/semestre2.
        if ($normalized === 'annuel') {
            return $this->resolver->annualSnapshot(
                (int) $etudiantId,
                (int) $anneeUniversitaireId
            );
        }

        return $this->resolver->snapshot(
            (int) $etudiantId,
            (int) $anneeUniversitaireId,
            $normalized
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
