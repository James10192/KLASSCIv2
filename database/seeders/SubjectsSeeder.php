<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Création des matières
        // Les matières sont comme les différents ingrédients d'une recette de savoir
        // Chacune apporte sa saveur unique à l'éducation de l'élève
        $subjects = [
            ['name' => 'Mathématiques', 'code' => 'MATH', 'description' => 'Étude des nombres, des formes et des structures'],
            ['name' => 'Français', 'code' => 'FRAN', 'description' => 'Étude de la langue française et de sa littérature'],
            ['name' => 'Anglais', 'code' => 'ANGL', 'description' => 'Apprentissage de la langue anglaise'],
            ['name' => 'Histoire-Géographie', 'code' => 'HIST', 'description' => 'Étude de l\'histoire et de la géographie'],
            ['name' => 'Sciences Physiques', 'code' => 'PHYS', 'description' => 'Étude de la physique et de la chimie'],
            ['name' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'description' => 'Étude de la biologie et de la géologie'],
            ['name' => 'Éducation Physique et Sportive', 'code' => 'EPS', 'description' => 'Activités physiques et sportives'],
            ['name' => 'Arts Plastiques', 'code' => 'ARTS', 'description' => 'Expression artistique et créativité'],
            ['name' => 'Musique', 'code' => 'MUSI', 'description' => 'Éducation musicale'],
            ['name' => 'Technologie', 'code' => 'TECH', 'description' => 'Étude des technologies et de l\'informatique'],
            ['name' => 'Philosophie', 'code' => 'PHIL', 'description' => 'Réflexion sur les grandes questions de l\'existence'],
            ['name' => 'Économie', 'code' => 'ECON', 'description' => 'Étude des mécanismes économiques'],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }

        $this->command->info('Matières créées avec succès!');
    }
}
