<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Str;

class ESBTPFiliereNiveauSeeder extends Seeder
{
    /**
     * Seeder pour créer les relations entre filières et niveaux d'études.
     *
     * @return void
     */
    public function run()
    {
        // Récupérer les niveaux d'études
        $niveauBTS1 = ESBTPNiveauEtude::where('code', 'BTS1')->first();
        $niveauBTS2 = ESBTPNiveauEtude::where('code', 'BTS2')->first();

        if (!$niveauBTS1 || !$niveauBTS2) {
            $this->command->error('Les niveaux d\'études BTS1 et BTS2 doivent être créés avant d\'exécuter ce seeder.');
            return;
        }

        // Récupérer toutes les filières
        $filieres = ESBTPFiliere::all();

        foreach ($filieres as $filiere) {
            // Déterminer le niveau d'étude en fonction du nom de la filière
            if (Str::contains($filiere->name, 'BTS1') || Str::contains($filiere->code, 'BTS1')) {
                // Associer la filière au niveau BTS1
                if (!$filiere->niveauxEtudes()->where('esbtp_niveau_etudes.id', $niveauBTS1->id)->exists()) {
                    $filiere->niveauxEtudes()->attach($niveauBTS1->id, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->command->info("Filière '{$filiere->name}' associée au niveau BTS1");
                }
            } elseif (Str::contains($filiere->name, 'BTS2') || Str::contains($filiere->code, 'BTS2')) {
                // Associer la filière au niveau BTS2
                if (!$filiere->niveauxEtudes()->where('esbtp_niveau_etudes.id', $niveauBTS2->id)->exists()) {
                    $filiere->niveauxEtudes()->attach($niveauBTS2->id, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->command->info("Filière '{$filiere->name}' associée au niveau BTS2");
                }
            } else {
                $this->command->warn("Impossible de déterminer le niveau d'étude pour la filière '{$filiere->name}'");
            }
        }

        $this->command->info('Les relations entre filières et niveaux d\'études ont été créées avec succès.');
    }
}
