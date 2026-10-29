<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ESBTPAnneeUniversitaireSeeder extends Seeder
{
    /**
     * Seeder pour créer les années universitaires de l'ESBTP de 2020 à 2040.
     *
     * @return void
     */
    public function run()
    {
        $anneesUniversitaires = [];
        $anneeActuelle = date('Y');

        // Génération des années universitaires de 2020 à 2040
        for ($annee = 2020; $annee <= 2040; $annee++) {
            $anneesUniversitaires[] = [
                'name' => $annee . '-' . ($annee + 1),
                'annee_debut' => $annee,
                'annee_fin' => $annee + 1,
                'start_date' => $annee . '-09-15',
                'end_date' => ($annee + 1) . '-07-15',
                'is_current' => ($annee == $anneeActuelle), // L'année actuelle est marquée comme courante
                'is_active' => ($annee >= $anneeActuelle - 1), // Les années récentes et futures sont actives
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Utilisation de la méthode insertOrIgnore pour éviter les doublons
        DB::table('esbtp_annee_universitaires')->insertOrIgnore($anneesUniversitaires);

        $this->command->info('Les années universitaires ESBTP de 2020 à 2040 ont été créées avec succès.');
    }
}
