<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Designation;

class DepartmentsAndDesignationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Création des départements
        // Les départements sont comme les différentes branches d'un arbre de connaissances
        $departments = [
            ['name' => 'Sciences', 'code' => 'SCI', 'description' => 'Département des sciences naturelles et physiques'],
            ['name' => 'Mathématiques', 'code' => 'MATH', 'description' => 'Département de mathématiques et statistiques'],
            ['name' => 'Langues', 'code' => 'LANG', 'description' => 'Département des langues et littérature'],
            ['name' => 'Histoire-Géographie', 'code' => 'HIST-GEO', 'description' => 'Département d\'histoire et géographie'],
            ['name' => 'Arts', 'code' => 'ART', 'description' => 'Département des arts et de la musique'],
            ['name' => 'Éducation Physique', 'code' => 'EPS', 'description' => 'Département d\'éducation physique et sportive'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }

        $this->command->info('Départements créés avec succès!');

        // Création des désignations
        // Les désignations sont comme les différents rangs dans une armée de l'éducation
        $designations = [
            ['name' => 'Professeur Principal', 'description' => 'Enseignant responsable d\'une classe'],
            ['name' => 'Professeur', 'description' => 'Enseignant standard'],
            ['name' => 'Directeur de Département', 'description' => 'Responsable d\'un département'],
            ['name' => 'Assistant', 'description' => 'Assistant d\'enseignement'],
            ['name' => 'Conseiller Pédagogique', 'description' => 'Conseiller pour les méthodes pédagogiques'],
        ];

        foreach ($designations as $desig) {
            Designation::create($desig);
        }

        $this->command->info('Désignations créées avec succès!');
    }
}
