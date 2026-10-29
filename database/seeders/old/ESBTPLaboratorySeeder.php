<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ESBTPLaboratorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get department IDs
        $departments = DB::table('esbtp_departments')->get();
        $departmentIds = $departments->pluck('id', 'code')->toArray();

        $laboratories = [
            [
                'name' => 'Laboratoire de Matériaux',
                'code' => 'LAB-MAT',
                'description' => 'Laboratoire d\'essais des matériaux de construction',
                'department_id' => $departmentIds['GC'],
                'location' => 'Bâtiment A, Niveau -1',
                'capacity' => 30,
                'equipment' => json_encode([
                    'Machine de compression',
                    'Four à béton',
                    'Tamiseuse électrique',
                    'Balance de précision'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Laboratoire de Mécanique',
                'code' => 'LAB-MEC',
                'description' => 'Laboratoire de mécanique et d\'essais mécaniques',
                'department_id' => $departmentIds['GM'],
                'location' => 'Bâtiment B, Niveau -1',
                'capacity' => 25,
                'equipment' => json_encode([
                    'Machine de traction',
                    'Banc d\'essai moteur',
                    'Équipement de métrologie',
                    'Machines-outils CNC'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Laboratoire d\'Électronique',
                'code' => 'LAB-ELEC',
                'description' => 'Laboratoire d\'électronique et d\'automatisme',
                'department_id' => $departmentIds['GE'],
                'location' => 'Bâtiment C, Niveau -1',
                'capacity' => 20,
                'equipment' => json_encode([
                    'Oscilloscopes',
                    'Générateurs de signaux',
                    'Stations de soudage',
                    'Kits Arduino et Raspberry Pi'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($laboratories as $laboratory) {
            DB::table('esbtp_laboratories')->insert($laboratory);
        }
    }
}
