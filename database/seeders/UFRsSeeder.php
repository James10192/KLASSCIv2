<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UFR;
use App\Models\User;

class UFRsSeeder extends Seeder
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

        // Création des UFRs
        // Les UFRs sont comme les grandes branches d'un arbre universitaire
        // Chaque UFR regroupe des formations dans un domaine spécifique
        $ufrs = [
            [
                'code' => 'SFA',
                'name' => 'Sciences Fondamentales et Appliquées',
                'description' => 'UFR regroupant les formations en mathématiques, physique, chimie et informatique',
                'director_id' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'SESA',
                'name' => 'Sciences Économiques et Sciences de l\'Administration',
                'description' => 'UFR regroupant les formations en économie, gestion et administration',
                'director_id' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'SHS',
                'name' => 'Sciences Humaines et Sociales',
                'description' => 'UFR regroupant les formations en sociologie, psychologie, histoire et géographie',
                'director_id' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'SJAP',
                'name' => 'Sciences Juridiques, Administratives et Politiques',
                'description' => 'UFR regroupant les formations en droit, sciences politiques et administration publique',
                'director_id' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'SSM',
                'name' => 'Sciences de la Santé et de la Médecine',
                'description' => 'UFR regroupant les formations en médecine, pharmacie et sciences de la santé',
                'director_id' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'STRM',
                'name' => 'Sciences de la Terre et des Ressources Minières',
                'description' => 'UFR regroupant les formations en géologie, mines et ressources naturelles',
                'director_id' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
            [
                'code' => 'BTP',
                'name' => 'Bâtiment et Travaux Publics',
                'description' => 'UFR regroupant les formations en génie civil, architecture et travaux publics',
                'director_id' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ],
        ];

        foreach ($ufrs as $ufr) {
            UFR::create($ufr);
        }

        $this->command->info('UFRs créées avec succès!');
    }
}
