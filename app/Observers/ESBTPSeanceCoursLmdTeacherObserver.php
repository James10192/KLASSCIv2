<?php

namespace App\Observers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Services\LMD\EnseignantDeClasseLmd;
use Illuminate\Validation\ValidationException;

/**
 * Garde backend des séances LMD.
 *
 * `teacher_id` pointe sur ESBTPTeacher alors que le planning/evaluation pointe
 * sur User. Cette garde traduit entre les deux et applique la même vérité par
 * classe, quel que soit l'écran/API qui crée la séance.
 */
final class ESBTPSeanceCoursLmdTeacherObserver
{
    public function creating(ESBTPSeanceCours $seance): void
    {
        $this->synchroniser($seance);
    }

    public function updating(ESBTPSeanceCours $seance): void
    {
        if ($seance->isDirty(['classe_id', 'matiere_id', 'annee_universitaire_id', 'teacher_id'])) {
            $this->synchroniser($seance);
        }
    }

    private function synchroniser(ESBTPSeanceCours $seance): void
    {
        if (! $seance->classe_id || ! $seance->matiere_id || ! $seance->annee_universitaire_id) {
            return;
        }

        $classe = ESBTPClasse::find($seance->classe_id);
        if (! $classe || ($classe->systeme_academique ?? '') !== 'LMD') {
            return;
        }

        $service = app(EnseignantDeClasseLmd::class);
        $resolution = $service->resoudre(
            $classe,
            (int) $seance->matiere_id,
            (int) $seance->annee_universitaire_id,
            null,
        );

        if (! $resolution['dans_maquette']) {
            throw ValidationException::withMessages([
                'matiere_id' => $resolution['message'] ?? 'Cet ECUE ne figure pas dans la maquette LMD de cette classe.',
            ]);
        }

        if ($resolution['enseignant_id']) {
            $profil = ESBTPTeacher::where('user_id', $resolution['enseignant_id'])->first();
            if (! $profil) {
                throw ValidationException::withMessages([
                    'teacher_id' => 'Le professeur résolu existe comme utilisateur mais pas comme profil enseignant. Complétez sa fiche enseignant avant de créer la séance.',
                ]);
            }
            $seance->teacher_id = $profil->id;
            return;
        }

        // En situation ambiguë, un choix explicite dans le formulaire est
        // accepté s'il correspond à l'un des professeurs candidats du planning
        // ou déjà observés sur cette classe.
        if ($seance->teacher_id) {
            $profil = ESBTPTeacher::with('user')->find($seance->teacher_id);
            if ($profil?->user && $service->candidatAutorise($resolution, (int) $profil->user_id)) {
                return;
            }

            // Une personne ayant le droit d'éditer le planning peut aussi
            // confirmer directement un autre profil existant : il est alors
            // ajouté au pool et toutes les traces de la classe sont harmonisées.
            if ($profil?->user && auth()->user()?->can('lmd.planning.edit')) {
                $service->confirmer(
                    $classe,
                    (int) $seance->matiere_id,
                    (int) $seance->annee_universitaire_id,
                    $resolution['semestre'],
                    (int) $profil->user_id,
                    (int) auth()->id(),
                );
                return;
            }
        }

        throw ValidationException::withMessages([
            'teacher_id' => ($resolution['message'] ?? 'Le professeur de cette classe doit être confirmé.').' Choisissez le professeur dans la liste proposée avant d’enregistrer la séance.',
        ]);
    }
}
