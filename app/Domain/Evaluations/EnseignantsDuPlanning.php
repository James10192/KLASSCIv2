<?php

namespace App\Domain\Evaluations;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Support\Collection;

/**
 * Source unique de l'affectation enseignant d'une evaluation LMD.
 *
 * Ordre de verite :
 *  1. planning academique LMD de la classe / annee / semestre / ECUE ;
 *  2. evaluations du meme semestre, uniquement pour les snapshots legacy ou
 *     lorsqu'aucun enseignant n'est encore porte par le planning.
 *
 * Une evaluation creee depuis l'ecran, Nanan, un examen planifie ou une
 * seance passe donc par la meme resolution : le modele ESBTPEvaluation appelle
 * appliquerAEvaluation() a l'enregistrement.
 */
final class EnseignantsDuPlanning
{
    /**
     * @return array{
     *   lmd: bool,
     *   semestre: int|null,
     *   planification_id: int|null,
     *   enseignant_principal_id: int|null,
     *   enseignants: array<int, array{id:int,name:string}>,
     *   source: string
     * }
     */
    public function pour(
        ESBTPClasse $classe,
        int $matiereId,
        ?int $anneeUniversitaireId,
        mixed $periode,
    ): array {
        $lmd = (string) $classe->systeme_academique === 'LMD';
        $semestre = $this->semestre($periode);

        if (! $lmd || ! $matiereId || ! $anneeUniversitaireId || ! $semestre || ! $classe->niveau_etude_id) {
            return $this->vide($lmd, $semestre);
        }

        $classe->loadMissing('parcours:id,filiere_id');
        $filiereId = $classe->parcours?->filiere_id ?: $classe->filiere_id;
        if (! $filiereId) {
            return $this->vide(true, $semestre);
        }

        $planification = ESBTPPlanificationAcademique::query()
            ->where('matiere_id', $matiereId)
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('semestre', $semestre)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('is_active', true)
            ->with(['enseignantPrincipal:id,name', 'teachers.user:id,name'])
            ->orderByDesc('id')
            ->first();

        if (! $planification) {
            return $this->vide(true, $semestre);
        }

        $enseignants = collect();
        if ($planification->enseignantPrincipal) {
            $enseignants->push($planification->enseignantPrincipal);
        }
        foreach ($planification->teachers as $teacher) {
            if ($teacher->user) {
                $enseignants->push($teacher->user);
            }
        }

        $enseignants = $enseignants
            ->unique('id')
            ->values()
            ->map(fn ($user) => [
                'id' => (int) $user->id,
                'name' => trim((string) $user->name),
            ])
            ->all();

        $principal = $planification->enseignant_principal_id
            ? (int) $planification->enseignant_principal_id
            : (count($enseignants) === 1 ? (int) $enseignants[0]['id'] : null);

        return [
            'lmd' => true,
            'semestre' => $semestre,
            'planification_id' => (int) $planification->id,
            'enseignant_principal_id' => $principal,
            'enseignants' => $enseignants,
            'source' => $enseignants === [] ? 'planning_sans_enseignant' : 'planning',
        ];
    }

    /**
     * Une evaluation LMD reprend l'enseignant principal du planning. Si le
     * planning contient un seul enseignant sans principal explicite, ce seul
     * enseignant est retenu. En l'absence d'affectation planifiee, on ne devine
     * rien : l'assignation explicite deja posee par le caller est conservee.
     */
    public function appliquerAEvaluation(ESBTPEvaluation $evaluation): void
    {
        if (! $evaluation->classe_id || ! $evaluation->matiere_id || ! $evaluation->annee_universitaire_id) {
            return;
        }

        $classe = $evaluation->relationLoaded('classe')
            ? $evaluation->classe
            : ESBTPClasse::find($evaluation->classe_id);
        if (! $classe || (string) $classe->systeme_academique !== 'LMD') {
            return;
        }

        $contexte = $this->pour(
            $classe,
            (int) $evaluation->matiere_id,
            (int) $evaluation->annee_universitaire_id,
            $evaluation->periode,
        );

        $enseignantId = $contexte['enseignant_principal_id'];
        if (! $enseignantId) {
            return;
        }

        $evaluation->enseignant_id = $enseignantId;
        $evaluation->enseignant_externe_nom = null;
    }

    /**
     * Enseignant(s) a figer sur un bulletin LMD. Le planning gagne toujours.
     * Les evaluations ne servent que de repli pour les donnees historiques ou
     * les ECUE dont le planning n'a encore aucun enseignant.
     *
     * @return array{enseignant_id:int|null,nom:string,source:string,planification_id:int|null}
     */
    public function pourBulletin(ESBTPLMDBulletin $bulletin, int $matiereId): array
    {
        $classe = $bulletin->relationLoaded('classe')
            ? $bulletin->classe
            : ESBTPClasse::find($bulletin->classe_id);

        if ($classe) {
            $planning = $this->pour(
                $classe,
                $matiereId,
                (int) $bulletin->annee_universitaire_id,
                (int) $bulletin->semestre,
            );

            if ($planning['enseignants'] !== []) {
                return [
                    'enseignant_id' => $planning['enseignant_principal_id'],
                    'nom' => collect($planning['enseignants'])->pluck('name')->filter()->unique()->implode(' / '),
                    'source' => 'planning',
                    'planification_id' => $planning['planification_id'],
                ];
            }
        }

        $legacy = $this->depuisEvaluations($bulletin, $matiereId);
        $legacy['planification_id'] = null;

        return $legacy;
    }

    /**
     * @return array{enseignant_id:int|null,nom:string,source:string}
     */
    private function depuisEvaluations(ESBTPLMDBulletin $bulletin, int $matiereId): array
    {
        $semestre = (int) $bulletin->semestre;
        $periodes = [
            (string) $semestre,
            'semestre'.$semestre,
            'S'.$semestre,
            'Semestre '.$semestre,
            'semestre '.$semestre,
        ];

        $evaluations = ESBTPEvaluation::query()
            ->where('matiere_id', $matiereId)
            ->where('classe_id', $bulletin->classe_id)
            ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
            ->whereIn('periode', $periodes)
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->where(function ($query) {
                $query->whereNotNull('enseignant_id')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('enseignant_externe_nom')
                            ->where('enseignant_externe_nom', '<>', '');
                    });
            })
            ->with('enseignant:id,name')
            ->orderBy('date_evaluation')
            ->orderBy('id')
            ->get();

        $noms = $evaluations
            ->map(fn (ESBTPEvaluation $evaluation): string => trim((string) (
                $evaluation->enseignant?->name
                ?: $evaluation->enseignant_externe_nom
                ?: ''
            )))
            ->filter()
            ->unique(fn (string $nom): string => mb_strtolower($nom, 'UTF-8'))
            ->values();

        return [
            'enseignant_id' => $evaluations
                ->first(fn (ESBTPEvaluation $evaluation) => $evaluation->enseignant_id !== null)?->enseignant_id,
            'nom' => $noms->implode(' / '),
            'source' => $noms->isEmpty() ? 'aucun' : 'evaluations',
        ];
    }

    private function semestre(mixed $periode): ?int
    {
        $valeur = trim((string) $periode);
        if ($valeur === '') {
            return null;
        }

        if (preg_match('/(\d{1,2})/', $valeur, $match) !== 1) {
            return null;
        }

        $semestre = (int) $match[1];

        return $semestre >= 1 && $semestre <= 10 ? $semestre : null;
    }

    private function vide(bool $lmd, ?int $semestre): array
    {
        return [
            'lmd' => $lmd,
            'semestre' => $semestre,
            'planification_id' => null,
            'enseignant_principal_id' => null,
            'enseignants' => [],
            'source' => $lmd ? 'planning_absent' : 'hors_lmd',
        ];
    }
}
