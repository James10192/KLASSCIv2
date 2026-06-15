<?php

namespace App\Services;

use App\Enums\TypeSeance;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPTeacherAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Source unique de vérité des HEURES PRÉCISES réalisées par un enseignant.
 *
 * Partagé par la pédagogie (pages teacher-attendance / coordinateur — heures
 * seulement) ET par la comptabilité (page Paie — heures × taux). Aucune logique
 * monétaire ici : ce service ne connaît pas les taux.
 *
 * Décisions design (grill juin 2026) :
 *  - On compte les HEURES réelles (durée des séances), jamais le nombre de séances.
 *  - Le planifié sert de baromètre (taux de réalisation), JAMAIS de base de paie.
 *  - Capture hybride : si la séance a un émargement enseignant, il fait foi ;
 *    sinon la saisie manuelle du coordinateur prendra le relais (PR2 — colonnes
 *    heure réelle). Pour l'instant la durée planifiée de la séance réalisée
 *    sert de meilleure estimation des heures réalisées.
 *  - Warnings de ponctualité (retard) et de séance non émargée sont exposés.
 *
 * @see App\Enums\TypeSeance — types et helper isVolumeTracked() (CM/TD/TP)
 */
class TeacherHoursService
{
    /**
     * Statuts d'émargement enseignant qui valent « séance réalisée ».
     * (les présences enseignants utilisent un vocabulaire mixte selon les écrans).
     */
    private const STATUTS_REALISES = ['present', 'présent', 'presente', 'late', 'retard', 'en_retard', 'fait'];

    /** Statuts d'émargement qui signalent un retard (warning ponctualité). */
    private const STATUTS_RETARD = ['late', 'retard', 'en_retard'];

    /**
     * Résumé des heures d'un enseignant sur une période, ventilé par type de séance.
     *
     * @param  array{classe_id?:int|null,type_seance?:string|null,annee_universitaire_id?:int|null}  $filtres
     * @return array{
     *     periode: array{from:string,to:string},
     *     par_type: array<string, array<string,mixed>>,
     *     totaux: array<string,float|int>,
     *     warnings: array<int, array<string,mixed>>,
     *     taux_realisation: float
     * }
     */
    public function summary(ESBTPTeacher $teacher, Carbon $from, Carbon $to, array $filtres = []): array
    {
        $seances = $this->seancesDeLaPeriode($teacher, $from, $to, $filtres);
        $emargements = $this->emargementsParSeance($teacher, $seances->pluck('id')->all());

        $parType = [];
        $warnings = [];

        foreach ($seances as $seance) {
            $type = $seance->type_seance instanceof TypeSeance
                ? $seance->type_seance
                : TypeSeance::fromLegacy($seance->type_seance);
            $key = $type->value;

            if (!isset($parType[$key])) {
                $parType[$key] = [
                    'type'              => $key,
                    'label'             => $type->label(),
                    'facturable'        => $type->isVolumeTracked(),
                    'nb_seances'        => 0,
                    'nb_realisees'      => 0,
                    'heures_planifiees' => 0.0,
                    'heures_realisees'  => 0.0,
                ];
            }

            $dureeH = $this->dureeHeures($seance);
            $parType[$key]['nb_seances']++;
            $parType[$key]['heures_planifiees'] += $dureeH;

            $rowsEmargement = $emargements->get($seance->id, collect());
            $realisee = $this->estRealisee($rowsEmargement);

            if ($realisee) {
                $parType[$key]['nb_realisees']++;
                // Meilleure estimation actuelle : durée planifiée de la séance réalisée.
                // (PR2 remplacera par la durée réelle émargée / saisie coordinateur.)
                $parType[$key]['heures_realisees'] += $dureeH;
            }

            foreach ($this->warningsSeance($seance, $rowsEmargement, $realisee) as $w) {
                $warnings[] = $w;
            }
        }

        // Tri stable : CM, TD, TP d'abord, puis le reste dans l'ordre de l'enum.
        $ordre = array_flip(TypeSeance::values());
        uasort($parType, fn ($a, $b) => ($ordre[$a['type']] ?? 99) <=> ($ordre[$b['type']] ?? 99));

        $totaux = $this->totaux($parType);
        $tauxReal = $totaux['heures_planifiees'] > 0
            ? round($totaux['heures_realisees'] / $totaux['heures_planifiees'] * 100, 1)
            : 0.0;

        return [
            'periode'          => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'par_type'         => array_values($parType),
            'totaux'           => $totaux,
            'warnings'         => $warnings,
            'taux_realisation' => $tauxReal,
        ];
    }

    /**
     * Séances d'un enseignant sur la période (date_seance), filtrables.
     *
     * @param  array<string,mixed>  $filtres
     * @return \Illuminate\Support\Collection<int, ESBTPSeanceCours>
     */
    public function seancesDeLaPeriode(ESBTPTeacher $teacher, Carbon $from, Carbon $to, array $filtres = []): Collection
    {
        $query = ESBTPSeanceCours::query()
            ->where('teacher_id', $teacher->id)
            ->whereNotNull('date_seance')
            ->whereDate('date_seance', '>=', $from->toDateString())
            ->whereDate('date_seance', '<=', $to->toDateString());

        if (!empty($filtres['classe_id'])) {
            $query->where('classe_id', $filtres['classe_id']);
        }
        if (!empty($filtres['type_seance'])) {
            $query->where('type_seance', $filtres['type_seance']);
        }
        if (!empty($filtres['annee_universitaire_id'])) {
            $query->where('annee_universitaire_id', $filtres['annee_universitaire_id']);
        }

        return $query->with(['classe:id,name', 'matiere:id,name'])
            ->orderBy('date_seance')
            ->get();
    }

    /**
     * Émargements enseignant groupés par séance (course_id).
     * Note : esbtp_teacher_attendances.teacher_id référence users.id (= teacher->user_id).
     *
     * @param  array<int>  $seanceIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, ESBTPTeacherAttendance>>
     */
    private function emargementsParSeance(ESBTPTeacher $teacher, array $seanceIds): Collection
    {
        if (empty($seanceIds) || empty($teacher->user_id)) {
            return collect();
        }

        return ESBTPTeacherAttendance::query()
            ->where('teacher_id', $teacher->user_id)
            ->whereIn('course_id', $seanceIds)
            ->get()
            ->groupBy('course_id');
    }

    /** Durée planifiée d'une séance en heures (depuis heure_debut / heure_fin). */
    private function dureeHeures(ESBTPSeanceCours $seance): float
    {
        if (!$seance->heure_debut || !$seance->heure_fin) {
            return 0.0;
        }

        $minutes = abs($seance->heure_fin->diffInMinutes($seance->heure_debut));

        return round($minutes / 60, 2);
    }

    /** Une séance est réalisée si au moins un émargement n'est pas une absence. */
    private function estRealisee(Collection $rowsEmargement): bool
    {
        if ($rowsEmargement->isEmpty()) {
            return false;
        }

        return $rowsEmargement->contains(function ($row) {
            return in_array(strtolower((string) $row->status), self::STATUTS_REALISES, true);
        });
    }

    /**
     * Warnings d'une séance : retard d'émargement, séance passée non émargée.
     * (Le dépassement d'horaire — overrun — arrivera en PR2 avec la durée réelle.)
     *
     * @return array<int, array<string,mixed>>
     */
    private function warningsSeance(ESBTPSeanceCours $seance, Collection $rowsEmargement, bool $realisee): array
    {
        $out = [];
        $estPassee = $seance->date_seance
            && Carbon::parse($seance->date_seance)->endOfDay()->isPast();

        $enRetard = $rowsEmargement->contains(function ($row) {
            return in_array(strtolower((string) $row->status), self::STATUTS_RETARD, true);
        });

        if ($enRetard) {
            $out[] = [
                'seance_id' => $seance->id,
                'type'      => 'retard',
                'severite'  => 'warning',
                'message'   => sprintf(
                    'Retard signalé — %s du %s',
                    $seance->matiere->name ?? 'séance',
                    Carbon::parse($seance->date_seance)->format('d/m/Y')
                ),
            ];
        }

        if ($estPassee && !$realisee) {
            $out[] = [
                'seance_id' => $seance->id,
                'type'      => 'non_emarge',
                'severite'  => 'danger',
                'message'   => sprintf(
                    'Séance non émargée — %s du %s',
                    $seance->matiere->name ?? 'séance',
                    Carbon::parse($seance->date_seance)->format('d/m/Y')
                ),
            ];
        }

        return $out;
    }

    /**
     * Agrège les totaux depuis la ventilation par type.
     *
     * @param  array<string, array<string,mixed>>  $parType
     * @return array<string, float|int>
     */
    private function totaux(array $parType): array
    {
        $t = [
            'nb_seances'                  => 0,
            'nb_realisees'                => 0,
            'heures_planifiees'           => 0.0,
            'heures_realisees'            => 0.0,
            'heures_realisees_facturables' => 0.0,
        ];

        foreach ($parType as $row) {
            $t['nb_seances']        += $row['nb_seances'];
            $t['nb_realisees']      += $row['nb_realisees'];
            $t['heures_planifiees'] += $row['heures_planifiees'];
            $t['heures_realisees']  += $row['heures_realisees'];
            if ($row['facturable']) {
                $t['heures_realisees_facturables'] += $row['heures_realisees'];
            }
        }

        $t['heures_planifiees'] = round($t['heures_planifiees'], 2);
        $t['heures_realisees']  = round($t['heures_realisees'], 2);
        $t['heures_realisees_facturables'] = round($t['heures_realisees_facturables'], 2);

        return $t;
    }
}
