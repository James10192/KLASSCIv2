<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ESBTPNiveauEtudeSeeder extends Seeder
{
    /**
     * Seeder pour créer les niveaux d'études de l'ESBTP.
     *
     * @return void
     */
    public function run()
    {
        $niveauxEtudes = [
            [
                'code' => 'BTS1',
                'name' => 'Première année BTS',
                'type' => 'BTS',
                'year' => 1,
                'description' => 'Niveau BTS première année - Formation sur 30 semaines',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BTS2',
                'name' => 'Deuxième année BTS',
                'type' => 'BTS',
                'year' => 2,
                'description' => 'Niveau BTS deuxième année - Formation sur 28 semaines',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Utilisation de la méthode insertOrIgnore pour éviter les doublons
        DB::table('esbtp_niveau_etudes')->insertOrIgnore($niveauxEtudes);

        $this->command->info('Les niveaux d\'études ESBTP ont été créés avec succès.');
    }
}
