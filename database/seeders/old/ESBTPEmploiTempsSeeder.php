<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\User;
use Carbon\Carbon;

class ESBTPEmploiTempsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Récupérer les classes
        $classes = ESBTPClasse::all();

        if ($classes->isEmpty()) {
            $this->command->info('Aucune classe trouvée. Veuillez d\'abord créer des classes.');
            return;
        }

        // Récupérer les matières
        $matieres = ESBTPMatiere::all();

        if ($matieres->isEmpty()) {
            $this->command->info('Aucune matière trouvée. Veuillez d\'abord créer des matières.');
            return;
        }

        // Récupérer les enseignants
        $enseignants = User::role('enseignant')->get();

        if ($enseignants->isEmpty()) {
            $this->command->info('Aucun enseignant trouvé. Les séances seront créées sans enseignant.');
        }

        // Récupérer un admin pour created_by
        $admin = User::role('superAdmin')->first();
        $adminId = $admin ? $admin->id : 1;

        foreach ($classes as $classe) {
            // Créer un emploi du temps pour chaque classe
            $emploiTemps = ESBTPEmploiTemps::create([
                'titre' => 'Emploi du temps - ' . $classe->name . ' - Semestre 1',
                'classe_id' => $classe->id,
                'semestre' => '1',
                'date_debut' => Carbon::now(),
                'date_fin' => Carbon::now()->addMonths(6),
                'is_active' => true,
                'is_current' => true,
                'created_by' => $adminId,
            ]);

            $this->command->info('Emploi du temps créé pour la classe: ' . $classe->name);

            // Créer des séances pour cet emploi du temps
            $this->createSessions($emploiTemps, $matieres, $enseignants, $adminId);
        }
    }

    /**
     * Créer des séances pour un emploi du temps.
     *
     * @param ESBTPEmploiTemps $emploiTemps
     * @param \Illuminate\Database\Eloquent\Collection $matieres
     * @param \Illuminate\Database\Eloquent\Collection $enseignants
     * @param int $adminId
     * @return void
     */
    private function createSessions($emploiTemps, $matieres, $enseignants, $adminId)
    {
        \Log::info('Début de la création des séances pour l\'emploi du temps:', [
            'emploi_temps_id' => $emploiTemps->id,
            'classe_id' => $emploiTemps->classe_id,
            'nombre_matieres' => $matieres->count(),
            'nombre_enseignants' => $enseignants->count()
        ]);

        // Créer des séances pour chaque jour de la semaine (0 = Lundi, 6 = Dimanche)
        $timeSlots = [
            ['08:00', '10:00'],
            ['10:00', '12:00'],
            ['13:00', '15:00'],
            ['15:00', '17:00'],
            ['17:00', '19:00']
        ];

        // Pour chaque jour de la semaine (sauf dimanche)
        for ($jour = 0; $jour < 6; $jour++) {
            // Choisir aléatoirement 2 à 4 créneaux horaires pour ce jour
            $numberOfSlots = rand(2, 4);
            $selectedSlots = array_rand($timeSlots, $numberOfSlots);
            if (!is_array($selectedSlots)) {
                $selectedSlots = [$selectedSlots];
            }

            foreach ($selectedSlots as $slotIndex) {
                // Choisir aléatoirement une matière
                $matiere = $matieres->random();

                // Choisir aléatoirement un enseignant (ou null si aucun enseignant n'est disponible)
                $enseignantId = $enseignants->isEmpty() ? null : $enseignants->random()->id;

                try {
                    // Créer la séance
                    $seance = ESBTPSeanceCours::create([
                        'emploi_temps_id' => $emploiTemps->id,
                        'matiere_id' => $matiere->id,
                        'enseignant_id' => $enseignantId,
                        'jour_semaine' => $jour,
                        'heure_debut' => $timeSlots[$slotIndex][0],
                        'heure_fin' => $timeSlots[$slotIndex][1],
                        'salle' => 'Salle ' . rand(100, 999),
                        'type_seance' => rand(0, 10) > 8 ? 'tp' : 'cours',
                        'is_active' => true,
                        'created_by' => $adminId,
                    ]);

                    \Log::info('Séance créée avec succès:', [
                        'seance_id' => $seance->id,
                        'jour' => $jour,
                        'horaire' => $timeSlots[$slotIndex][0] . '-' . $timeSlots[$slotIndex][1],
                        'matiere' => $matiere->name,
                        'enseignant_id' => $enseignantId
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de la création de la séance:', [
                        'message' => $e->getMessage(),
                        'jour' => $jour,
                        'horaire' => $timeSlots[$slotIndex][0] . '-' . $timeSlots[$slotIndex][1],
                        'matiere_id' => $matiere->id,
                        'enseignant_id' => $enseignantId
                    ]);
                }
            }
        }

        \Log::info('Fin de la création des séances pour l\'emploi du temps ' . $emploiTemps->id);
    }
}
