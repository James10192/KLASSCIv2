<?php

namespace App\Observers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\User;
use App\Services\LMD\EnseignantDePlanificationLmd;
use Illuminate\Validation\ValidationException;

/**
 * Une évaluation LMD créée/modifiée par un utilisateur prend son enseignant
 * depuis la planification académique de l'ECUE.
 *
 * Les régularisations historiques sont des conteneurs techniques de reprise de
 * notes, pas des évaluations pédagogiques nouvelles : elles gardent le repli
 * historique et ne bloquent pas une reprise de données ancienne.
 */
final class ESBTPEvaluationLmdTeacherObserver
{
    public function creating(ESBTPEvaluation $evaluation): void
    {
        $this->synchroniser($evaluation, strict: $this->doitEtreStrict($evaluation));
    }

    public function updating(ESBTPEvaluation $evaluation): void
    {
        $scopeChange = $evaluation->isDirty(['classe_id', 'matiere_id', 'periode', 'annee_universitaire_id']);
        $teacherChange = $evaluation->isDirty(['enseignant_id', 'enseignant_externe_nom']);

        if ($scopeChange || $teacherChange) {
            $this->synchroniser($evaluation, strict: $this->doitEtreStrict($evaluation));
        }
    }

    private function synchroniser(ESBTPEvaluation $evaluation, bool $strict): void
    {
        if (! $evaluation->classe_id || ! $evaluation->matiere_id || ! $evaluation->annee_universitaire_id) {
            return;
        }

        $classe = ESBTPClasse::find($evaluation->classe_id);
        if (! $classe || ($classe->systeme_academique ?? '') !== 'LMD') {
            return;
        }

        $resolution = app(EnseignantDePlanificationLmd::class)->resoudre(
            $classe,
            (int) $evaluation->matiere_id,
            (int) $evaluation->annee_universitaire_id,
            $evaluation->periode,
            false,
        );

        if (! $resolution['dans_maquette']) {
            if ($strict) {
                throw ValidationException::withMessages([
                    'matiere_id' => $resolution['message'] ?? 'Cet ECUE ne figure pas dans la maquette LMD de ce semestre.',
                ]);
            }

            return;
        }

        if ($resolution['enseignant_id']) {
            // La planification a la priorité absolue : aucune affectation libre
            // sur le formulaire ou l'API ne doit créer une troisième vérité.
            $evaluation->enseignant_id = $resolution['enseignant_id'];
            $evaluation->enseignant_externe_nom = null;

            return;
        }

        if (! $strict) {
            return;
        }

        $acteur = $this->acteur($evaluation);
        $action = $acteur?->can('lmd.planning.edit')
            ? 'Assignez rapidement un enseignant à cet ECUE dans la planification LMD, puis enregistrez de nouveau.'
            : 'Demandez à une personne ayant le droit de modifier le planning LMD d’affecter l’enseignant.';

        throw ValidationException::withMessages([
            'enseignant_id' => ($resolution['message'] ?? 'Aucun enseignant n’est affecté dans le planning LMD.').' '.$action,
        ]);
    }

    private function doitEtreStrict(ESBTPEvaluation $evaluation): bool
    {
        $titre = mb_strtolower(trim((string) $evaluation->titre), 'UTF-8');
        if (str_starts_with($titre, 'régularisation ') || str_starts_with($titre, 'regularisation ')) {
            return false;
        }

        // Les fixtures/seeders historiques sans auteur restent importables. Une
        // vraie création utilisateur (écran, API, Nanan, examen planifié) porte
        // toujours created_by ou un utilisateur authentifié.
        return $this->acteur($evaluation) !== null;
    }

    private function acteur(ESBTPEvaluation $evaluation): ?User
    {
        if (auth()->check()) {
            return auth()->user();
        }

        $id = $evaluation->created_by ?: $evaluation->updated_by;

        return $id ? User::find($id) : null;
    }
}
