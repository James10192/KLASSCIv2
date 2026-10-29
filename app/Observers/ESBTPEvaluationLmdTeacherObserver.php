<?php

namespace App\Observers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\User;
use App\Services\LMD\EnseignantDeClasseLmd;
use Illuminate\Validation\ValidationException;

/**
 * Une evaluation LMD consomme l'affectation REELLE de sa classe.
 *
 * Le planning peut contenir plusieurs enseignants pour le meme ECUE : on ne
 * choisit donc plus arbitrairement le principal. Une classe deja coherente est
 * resolue automatiquement ; un pool ambigu exige un choix explicite.
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

        $resolution = app(EnseignantDeClasseLmd::class)->resoudre(
            $classe,
            (int) $evaluation->matiere_id,
            (int) $evaluation->annee_universitaire_id,
            $evaluation->periode,
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
            $evaluation->enseignant_id = $resolution['enseignant_id'];
            $evaluation->enseignant_externe_nom = null;
            return;
        }

        // Premier acte pedagogique d'une classe : si le planning porte plusieurs
        // professeurs, le formulaire peut en fournir un explicitement. Il doit
        // appartenir au pool/candidats resolu ; cette evaluation devient ensuite
        // la preuve qui permettra aux ecrans suivants de choisir automatiquement.
        $choisi = (int) ($evaluation->enseignant_id ?: 0);
        if ($choisi && app(EnseignantDeClasseLmd::class)->candidatAutorise($resolution, $choisi)) {
            $evaluation->enseignant_externe_nom = null;
            return;
        }

        if (! $strict) {
            return;
        }

        $acteur = $this->acteur($evaluation);
        $action = $acteur?->can('lmd.planning.edit')
            ? 'Choisissez le professeur de cette classe dans le dialogue LMD. KLASSCI harmonisera ensuite les evaluations et les seances de cette classe.'
            : 'Demandez a une personne ayant le droit de modifier le planning LMD de confirmer le professeur de cette classe.';

        throw ValidationException::withMessages([
            'enseignant_id' => ($resolution['message'] ?? 'Le professeur de cette classe doit etre confirme.').' '.$action,
        ]);
    }

    private function doitEtreStrict(ESBTPEvaluation $evaluation): bool
    {
        $titre = mb_strtolower(trim((string) $evaluation->titre), 'UTF-8');
        if (str_starts_with($titre, 'régularisation ') || str_starts_with($titre, 'regularisation ')) {
            return false;
        }

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
