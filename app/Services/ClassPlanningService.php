<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use Illuminate\Support\Facades\DB;

class ClassPlanningService
{
    /**
     * Construit les données de planning matière pour une classe donnée.
     *
     * @param ESBTPClasse $classe
     * @param ESBTPAnneeUniversitaire|null $anneeCourante
     * @param string $periode  'annee', 'semestre1', or 'semestre2'
     * @param array|null $lmdSemestres  Optional [semA, semB] mapping for LMD classes (ex: [5,6] for L3). If null, defaults to [1,2] (BTS legacy).
     * @return array{matieres: \Illuminate\Support\Collection, enseignants?: \Illuminate\Support\Collection, stats: array}
     */
    public function buildPlanningMatierePourClasse(
        ESBTPClasse $classe,
        ?ESBTPAnneeUniversitaire $anneeCourante,
        string $periode,
        ?array $lmdSemestres = null,
    ): array {
        if (!$anneeCourante) {
            return [
                'matieres' => collect(),
                'stats' => [
                    'heures_planifiees' => 0,
                    'heures_realisees' => 0,
                    'nb_seances' => 0,
                    'taux_realisation' => 0,
                ],
            ];
        }

        // Mapping niveau-aware : L3 LMD → [5,6], L1 LMD → [1,2], BTS → [1,2] par défaut.
        $sem1Number = (int) ($lmdSemestres[0] ?? 1);
        $sem2Number = (int) ($lmdSemestres[1] ?? 2);

        // Pour le filtre séances, on accepte de multiples formats stockés en historique
        // (entier, string, "S1", "Semestre 1", etc.) — d'où la fonction helper.
        $sem1Variants = $this->semestreVariants($sem1Number);
        $sem2Variants = $this->semestreVariants($sem2Number);

        // Tronc commun (C9) : une classe de spécialité hérite des planifications
        // définies au niveau de sa filière mère (le tronc commun). On élargit donc
        // le filtre à l'union [filière classe, filière TC parente] via le helper modèle.
        $unionFiliereIds = $classe->filiere
            ? $classe->filiere->troncCommunUnionFiliereIds()
            : [$classe->filiere_id];

        $planificationsQuery = ESBTPPlanificationAcademique::with(['matiere'])
            ->where('annee_universitaire_id', $anneeCourante->id)
            ->whereIn('filiere_id', $unionFiliereIds)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->select(
                'matiere_id',
                DB::raw('SUM(volume_horaire_total) as heures_planifiees'),
            )
            ->groupBy('matiere_id');

        if ($periode === 'semestre1') {
            $planificationsQuery->where('semestre', $sem1Number);
        } elseif ($periode === 'semestre2') {
            $planificationsQuery->where('semestre', $sem2Number);
        }

        $planifications = $planificationsQuery->get()->keyBy('matiere_id');

        $seancesQuery = ESBTPSeanceCours::query()
            ->join(
                'esbtp_emploi_temps',
                'esbtp_seance_cours.emploi_temps_id',
                '=',
                'esbtp_emploi_temps.id',
            )
            ->leftJoin(
                DB::raw('(
                SELECT ta1.course_id, ta1.status
                FROM esbtp_teacher_attendances ta1
                INNER JOIN (
                    SELECT course_id,
                           MAX(CASE
                               WHEN DATE(date) = CURDATE() THEN CONCAT("1_", created_at)
                               WHEN DATE(date) = (SELECT DATE(date_seance) FROM esbtp_seance_cours WHERE id = course_id) THEN CONCAT("2_", created_at)
                               ELSE CONCAT("3_", created_at)
                           END) as max_priority
                    FROM esbtp_teacher_attendances
                    WHERE type = "start"
                    GROUP BY course_id
                ) ta2 ON ta1.course_id = ta2.course_id
                     AND CONCAT(
                         CASE
                             WHEN DATE(ta1.date) = CURDATE() THEN "1_"
                             WHEN DATE(ta1.date) = (SELECT DATE(date_seance) FROM esbtp_seance_cours WHERE id = ta1.course_id) THEN "2_"
                             ELSE "3_"
                         END, ta1.created_at
                     ) = ta2.max_priority
                WHERE ta1.type = "start"
            ) as latest_attendance'),
                'latest_attendance.course_id',
                '=',
                'esbtp_seance_cours.id',
            )
            ->where(function ($query) {
                $query
                    ->whereNull('latest_attendance.status')
                    ->orWhere('latest_attendance.status', '!=', 'absent');
            })
            ->where('esbtp_seance_cours.classe_id', $classe->id)
            ->where(
                'esbtp_emploi_temps.annee_universitaire_id',
                $anneeCourante->id,
            )
            ->select(
                'esbtp_seance_cours.matiere_id',
                'esbtp_seance_cours.teacher_id',
                DB::raw('COUNT(DISTINCT esbtp_seance_cours.id) as nb_seances'),
                DB::raw(
                    'SUM(TIME_TO_SEC(TIMEDIFF(esbtp_seance_cours.heure_fin, esbtp_seance_cours.heure_debut))/3600) as total_heures',
                ),
            )
            ->groupBy(
                'esbtp_seance_cours.matiere_id',
                'esbtp_seance_cours.teacher_id',
            )
            ->whereRaw('(
                esbtp_seance_cours.date_seance < CURDATE()
                OR (
                    esbtp_seance_cours.date_seance = CURDATE()
                    AND TIME(esbtp_seance_cours.heure_fin) <= TIME(NOW())
                )
            )');

        if ($periode === 'semestre1') {
            $seancesQuery->whereIn('esbtp_emploi_temps.semestre', $sem1Variants);
        } elseif ($periode === 'semestre2') {
            $seancesQuery->whereIn('esbtp_emploi_temps.semestre', $sem2Variants);
        }

        $seancesRealisees = $seancesQuery->get();

        $teacherIds = $seancesRealisees
            ->pluck('teacher_id')
            ->filter()
            ->unique();
        $teachers = ESBTPTeacher::with('user')
            ->whereIn('id', $teacherIds)
            ->get()
            ->keyBy('id');

        $matiereIds = $planifications
            ->keys()
            ->merge($seancesRealisees->pluck('matiere_id'))
            ->unique();
        $matieres = ESBTPMatiere::whereIn('id', $matiereIds)
            ->get()
            ->keyBy('id');

        $matieresData = $matiereIds
            ->map(function ($matiereId) use (
                $planifications,
                $seancesRealisees,
                $teachers,
                $matieres,
            ) {
                $planification = $planifications->get($matiereId);
                $heuresPlanifiees = $planification
                    ? (float) $planification->heures_planifiees
                    : 0;

                $seancesMatiere = $seancesRealisees->where(
                    'matiere_id',
                    $matiereId,
                );
                $totalHeures = (float) $seancesMatiere->sum('total_heures');
                $nbSeances = (int) $seancesMatiere->sum('nb_seances');

                $enseignants = $seancesMatiere
                    ->groupBy('teacher_id')
                    ->map(function ($items, $teacherId) use ($teachers) {
                        $teacher = $teachers->get($teacherId);
                        if (!$teacher) {
                            return null;
                        }

                        $teacherName = trim(
                            (string) ($teacher->title
                                ? $teacher->title . ' '
                                : '') .
                                ($teacher->name ?? ''),
                        );

                        return [
                            'id' => $teacher->id,
                            'name' => $teacherName ?: 'Enseignant',
                            'heures_realisees' => round(
                                (float) $items->sum('total_heures'),
                                2,
                            ),
                            'nb_seances' => (int) $items->sum('nb_seances'),
                        ];
                    })
                    ->filter()
                    ->values();

                $heuresRestantes = max(0, $heuresPlanifiees - $totalHeures);

                return [
                    'matiere' => $matieres->get($matiereId),
                    'heures_planifiees' => round($heuresPlanifiees, 2),
                    'heures_realisees' => round($totalHeures, 2),
                    'heures_restantes' => round($heuresRestantes, 2),
                    'nb_seances' => $nbSeances,
                    'pourcentage_realise' =>
                        $heuresPlanifiees > 0
                            ? round(($totalHeures / $heuresPlanifiees) * 100, 1)
                            : 0,
                    'est_configure' => $heuresPlanifiees > 0,
                    'enseignants' => $enseignants,
                ];
            })
            ->filter()
            ->sortBy(function ($item) {
                return $item['matiere']->name ?? '';
            })
            ->values();

        $enseignantsResume = $seancesRealisees
            ->groupBy('teacher_id')
            ->map(function ($items, $teacherId) use ($teachers) {
                $teacher = $teachers->get($teacherId);
                if (!$teacher) {
                    return null;
                }

                $teacherName = trim(
                    (string) ($teacher->title ? $teacher->title . ' ' : '') .
                        ($teacher->name ?? ''),
                );

                return [
                    'id' => $teacher->id,
                    'name' => $teacherName ?: 'Enseignant',
                    'heures_realisees' => round(
                        (float) $items->sum('total_heures'),
                        2,
                    ),
                    'nb_seances' => (int) $items->sum('nb_seances'),
                ];
            })
            ->filter()
            ->sortByDesc('heures_realisees')
            ->values();

        $totalPlanifiees = $matieresData->sum('heures_planifiees');
        $totalRealisees = $matieresData->sum('heures_realisees');
        $totalSeances = $matieresData->sum('nb_seances');
        $taux =
            $totalPlanifiees > 0
                ? round(($totalRealisees / $totalPlanifiees) * 100, 1)
                : 0;

        $matieresData = $matieresData
            ->map(function ($item) use ($totalRealisees) {
                $item['pourcentage'] =
                    $totalRealisees > 0
                        ? round(
                            ($item['heures_realisees'] / $totalRealisees) * 100,
                            1,
                        )
                        : 0;
                return $item;
            })
            ->values();

        return [
            'matieres' => $matieresData,
            'enseignants' => $enseignantsResume,
            'stats' => [
                'heures_planifiees' => round($totalPlanifiees, 2),
                'heures_realisees' => round($totalRealisees, 2),
                'nb_seances' => (int) $totalSeances,
                'taux_realisation' => $taux,
            ],
        ];
    }

    /**
     * Génère les variantes string acceptées pour le filtre `esbtp_emploi_temps.semestre`.
     * L'historique BTS stocke des formats hétérogènes ('1', 1, 'S1', 'Semestre 1', etc.).
     */
    private function semestreVariants(int $n): array
    {
        return [
            (string) $n, $n,
            "S{$n}", "s{$n}",
            "Semestre {$n}", "semestre{$n}", "Semestre{$n}",
            "SEMESTRE {$n}",
        ];
    }
}
