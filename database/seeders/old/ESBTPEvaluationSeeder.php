<?php

namespace Database\Seeders;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPClasse;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Database\Seeder;

class ESBTPEvaluationSeeder extends Seeder
{
    public function run()
    {
        $anneeUniversitaire = ESBTPAnneeUniversitaire::first();
        $classes = ESBTPClasse::all();
        $periodes = ['semestre1', 'semestre2'];
        $typesEvaluation = ['devoir', 'examen'];

        foreach ($classes as $classe) {
            $matieres = $classe->matieres;

            foreach ($matieres as $matiere) {
                foreach ($periodes as $periode) {
                    // Créer un devoir (coefficient 1)
                    ESBTPEvaluation::create([
                        'matiere_id' => $matiere->id,
                        'classe_id' => $classe->id,
                        'type' => 'devoir',
                        'periode' => $periode,
                        'date_evaluation' => now(),
                        'coefficient' => 1,
                        'annee_universitaire_id' => $anneeUniversitaire->id,
                        'created_by' => 1,
                        'updated_by' => 1
                    ]);

                    // Créer un examen (coefficient 2)
                    ESBTPEvaluation::create([
                        'matiere_id' => $matiere->id,
                        'classe_id' => $classe->id,
                        'type' => 'examen',
                        'periode' => $periode,
                        'date_evaluation' => now()->addDays(15),
                        'coefficient' => 2,
                        'annee_universitaire_id' => $anneeUniversitaire->id,
                        'created_by' => 1,
                        'updated_by' => 1
                    ]);
                }
            }
        }
    }
}
