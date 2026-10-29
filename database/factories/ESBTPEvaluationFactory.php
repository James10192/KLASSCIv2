<?php

namespace Database\Factories;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Database\Eloquent\Factories\Factory;

class ESBTPEvaluationFactory extends Factory
{
    protected $model = ESBTPEvaluation::class;

    public function definition()
    {
        return [
            'matiere_id' => ESBTPMatiere::factory(),
            'type' => $this->faker->randomElement(['Devoir', 'Examen', 'TP', 'Projet']),
            'date_evaluation' => $this->faker->dateTimeBetween('-1 year', '+1 month'),
            'coefficient' => $this->faker->numberBetween(1, 4),
            'bareme' => 20,
            'periode' => $this->faker->randomElement(['semestre1', 'semestre2']),
            'annee_universitaire_id' => ESBTPAnneeUniversitaire::factory(),
            // Premier utilisateur existant, nul sur une base vide : un 1 en dur viole
            // la clé étrangère vers users dès qu'aucun compte n'a été créé.
            'created_by' => fn () => \App\Models\User::query()->min('id'),
            'updated_by' => fn () => \App\Models\User::query()->min('id'),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
