<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classroom;
use App\Models\User;

class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Récupérer un utilisateur administrateur pour l'attribution
        $admin = User::where('role', 'admin')->first();
        $adminId = $admin ? $admin->id : null;

        // Création des salles de classe
        // Les salles sont comme des conteneurs
        // Chacune peut accueillir différents types d'activités pédagogiques
        $classrooms = [
            // Amphithéâtres
            [
                'code' => 'AMPHI-A',
                'name' => 'Amphithéâtre A',
                'description' => 'Grand amphithéâtre pour les cours magistraux',
                'building' => 'Bâtiment Principal',
                'floor' => 0,
                'capacity' => 200,
                'type' => 'Amphithéâtre',
                'has_projector' => true,
                'has_computers' => false,
                'has_whiteboard' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'AMPHI-B',
                'name' => 'Amphithéâtre B',
                'description' => 'Amphithéâtre de taille moyenne',
                'building' => 'Bâtiment Principal',
                'floor' => 0,
                'capacity' => 150,
                'type' => 'Amphithéâtre',
                'has_projector' => true,
                'has_computers' => false,
                'has_whiteboard' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Salles de TD
            [
                'code' => 'TD-101',
                'name' => 'Salle TD 101',
                'description' => 'Salle pour travaux dirigés',
                'building' => 'Bâtiment Principal',
                'floor' => 1,
                'capacity' => 40,
                'type' => 'Salle TD',
                'has_projector' => true,
                'has_computers' => false,
                'has_whiteboard' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'TD-102',
                'name' => 'Salle TD 102',
                'description' => 'Salle pour travaux dirigés',
                'building' => 'Bâtiment Principal',
                'floor' => 1,
                'capacity' => 40,
                'type' => 'Salle TD',
                'has_projector' => true,
                'has_computers' => false,
                'has_whiteboard' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'TD-201',
                'name' => 'Salle TD 201',
                'description' => 'Salle pour travaux dirigés',
                'building' => 'Bâtiment Principal',
                'floor' => 2,
                'capacity' => 35,
                'type' => 'Salle TD',
                'has_projector' => true,
                'has_computers' => false,
                'has_whiteboard' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Laboratoires informatiques
            [
                'code' => 'LAB-INFO-1',
                'name' => 'Laboratoire Informatique 1',
                'description' => 'Salle équipée d\'ordinateurs pour les TP',
                'building' => 'Bâtiment Informatique',
                'floor' => 1,
                'capacity' => 30,
                'type' => 'Laboratoire',
                'has_projector' => true,
                'has_computers' => true,
                'has_whiteboard' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'LAB-INFO-2',
                'name' => 'Laboratoire Informatique 2',
                'description' => 'Salle équipée d\'ordinateurs pour les TP',
                'building' => 'Bâtiment Informatique',
                'floor' => 1,
                'capacity' => 30,
                'type' => 'Laboratoire',
                'has_projector' => true,
                'has_computers' => true,
                'has_whiteboard' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'LAB-INFO-3',
                'name' => 'Laboratoire Informatique 3',
                'description' => 'Salle équipée d\'ordinateurs pour les TP avancés',
                'building' => 'Bâtiment Informatique',
                'floor' => 2,
                'capacity' => 25,
                'type' => 'Laboratoire',
                'has_projector' => true,
                'has_computers' => true,
                'has_whiteboard' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],

            // Laboratoires spécialisés
            [
                'code' => 'LAB-ELEC',
                'name' => 'Laboratoire d\'Électronique',
                'description' => 'Salle équipée pour les TP d\'électronique',
                'building' => 'Bâtiment Sciences',
                'floor' => 1,
                'capacity' => 20,
                'type' => 'Laboratoire',
                'has_projector' => true,
                'has_computers' => true,
                'has_whiteboard' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'LAB-RESEAU',
                'name' => 'Laboratoire Réseaux',
                'description' => 'Salle équipée pour les TP de réseaux informatiques',
                'building' => 'Bâtiment Informatique',
                'floor' => 2,
                'capacity' => 20,
                'type' => 'Laboratoire',
                'has_projector' => true,
                'has_computers' => true,
                'has_whiteboard' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        ];

        foreach ($classrooms as $classroom) {
            Classroom::create($classroom);
        }

        $this->command->info('Salles de classe créées avec succès!');
    }
}
