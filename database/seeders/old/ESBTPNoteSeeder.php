<?php

namespace Database\Seeders;

use App\Models\ESBTPNote;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPEtudiant;
use Illuminate\Database\Seeder;

class ESBTPNoteSeeder extends Seeder
{
    public function run()
    {
        $evaluations = ESBTPEvaluation::all();

        foreach ($evaluations as $evaluation) {
            $etudiants = ESBTPEtudiant::where('classe_id', $evaluation->classe_id)->get();

            foreach ($etudiants as $etudiant) {
                // Générer une note aléatoire entre 0 et 20
                $note = mt_rand(8 * 10, 18 * 10) / 10; // Pour avoir des notes entre 8 et 18 avec une décimale

                ESBTPNote::create([
                    'etudiant_id' => $etudiant->id,
                    'evaluation_id' => $evaluation->id,
                    'valeur' => $note,
                    'created_by' => 1,
                    'updated_by' => 1
                ]);
            }
        }
    }
}
