<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassModel;
use App\Models\Section;

class ClassesAndSectionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Création des classes
        // Les classes sont comme les étages d'un immeuble de connaissances
        // Plus on monte, plus on acquiert de savoir
        $classes = [
            ['name' => '6ème', 'description' => 'Première année du collège'],
            ['name' => '5ème', 'description' => 'Deuxième année du collège'],
            ['name' => '4ème', 'description' => 'Troisième année du collège'],
            ['name' => '3ème', 'description' => 'Quatrième année du collège'],
            ['name' => '2nde', 'description' => 'Première année du lycée'],
            ['name' => '1ère', 'description' => 'Deuxième année du lycée'],
            ['name' => 'Terminale', 'description' => 'Dernière année du lycée'],
        ];

        foreach ($classes as $class) {
            ClassModel::create($class);
        }

        $this->command->info('Classes créées avec succès!');

        // Création des sections
        // Les sections sont comme les différentes pièces d'un même étage
        // Chaque pièce a sa propre ambiance mais est au même niveau
        $sections = [
            ['name' => 'A', 'description' => 'Section A'],
            ['name' => 'B', 'description' => 'Section B'],
            ['name' => 'C', 'description' => 'Section C'],
            ['name' => 'D', 'description' => 'Section D'],
        ];

        foreach ($sections as $section) {
            Section::create($section);
        }

        $this->command->info('Sections créées avec succès!');
    }
}
