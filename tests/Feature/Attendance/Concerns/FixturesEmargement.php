<?php

namespace Tests\Feature\Attendance\Concerns;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Un enseignant (compte + profil) et ses séances, pour les tests d'émargement.
 * Rappel : la séance porte l'id du PROFIL, l'émargement celui du COMPTE.
 */
trait FixturesEmargement
{
    protected function makeTeacherUser(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->assignRole('enseignant');
        $user->givePermissionTo(['attendances.sign', 'attendances.view_own']);

        ESBTPTeacher::create([
            'user_id' => $user->id,
            'matricule' => 'ENS-' . $user->id,
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    protected function insertTeacherProfile(int $id, int $userId): int
    {
        DB::table('esbtp_teachers')->insert([
            'id' => $id,
            'user_id' => $userId,
            'matricule' => 'ENS-MIG-' . $id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    protected function makeSeance(ESBTPTeacher $teacher, Carbon $date, string $debut, string $fin): ESBTPSeanceCours
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create();
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $matiere = ESBTPMatiere::factory()->create();
        $emploiTemps = ESBTPEmploiTemps::create([
            'titre' => 'Planning test émargement',
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'semestre' => 'semestre1',
            'date_debut' => $date->copy()->subMonth()->toDateString(),
            'date_fin' => $date->copy()->addMonth()->toDateString(),
            'is_active' => true,
            'is_current' => true,
        ]);

        return ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploiTemps->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'teacher_id' => $teacher->id,
            'jour' => 'lundi',
            'heure_debut' => $debut,
            'heure_fin' => $fin,
            'annee_universitaire_id' => $annee->id,
            'date_seance' => $date->toDateString(),
            'type' => ESBTPSeanceCours::TYPE_COURSE,
            'type_seance' => 'cours',
            'is_active' => true,
        ]);
    }
}
