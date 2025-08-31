<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPBulletinDetail;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPResultatMatiere;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ESBTPBulletinDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get all bulletins
        $bulletins = ESBTPBulletin::with(['classe', 'resultatsMatiere'])->get();

        // Track our progress
        $total = count($bulletins);
        $current = 0;

        $this->command->info("Starting to migrate data for $total bulletins...");

        foreach ($bulletins as $bulletin) {
            $current++;

            // Get all subjects from the class
            $matieres = $bulletin->classe->matieres;

            if ($matieres->isEmpty()) {
                $this->command->warn("Bulletin #{$bulletin->id} - No subjects found for class #{$bulletin->classe_id}");
                continue;
            }

            $this->command->info("Processing bulletin #{$bulletin->id} ($current/$total) - {$matieres->count()} subjects to process");

            // For each subject, find the associated result or create a new detail
            foreach ($matieres as $matiere) {
                try {
                    // Check if we have a result for this subject
                    $resultat = $bulletin->resultatsMatiere()
                        ->where('matiere_id', $matiere->id)
                        ->first();

                    // Create the detail
                    $detail = new ESBTPBulletinDetail();
                    $detail->bulletin_id = $bulletin->id;
                    $detail->matiere_id = $matiere->id;

                    // If we have a result, use its data
                    if ($resultat) {
                        $detail->moyenne = $resultat->moyenne;
                        $detail->coefficient = $resultat->coefficient;
                        $detail->rang = $resultat->rang;
                        $detail->appreciation = $resultat->appreciation;

                        // Try to get note_cc and note_examen from config or default values
                        $noteCC = 0;
                        $noteExamen = 0;

                        // If moyenne is available, approximate CC and Examen notes
                        if ($resultat->moyenne) {
                            $noteCC = round($resultat->moyenne * 0.4, 2); // Assume 40% for CC
                            $noteExamen = round($resultat->moyenne * 0.6, 2); // Assume 60% for Examen
                        }

                        $detail->note_cc = $noteCC;
                        $detail->note_examen = $noteExamen;
                    } else {
                        // Default values
                        $detail->coefficient = $matiere->coefficient;
                    }

                    // Set common values
                    $detail->effectif = $bulletin->effectif_classe;

                    // Save the detail
                    $detail->save();

                } catch (\Exception $e) {
                    $this->command->error("Error processing bulletin #{$bulletin->id}, matiere #{$matiere->id}: " . $e->getMessage());
                    Log::error("Bulletin Details Seeder error", [
                        'bulletin_id' => $bulletin->id,
                        'matiere_id' => $matiere->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        $this->command->info("Bulletin details migration completed. Total bulletins processed: $total");
    }
}
