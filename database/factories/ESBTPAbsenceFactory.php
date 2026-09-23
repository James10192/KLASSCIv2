<?php

namespace Database\Factories;

use App\Models\ESBTPAbsence;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use Illuminate\Database\Eloquent\Factories\Factory;

class ESBTPAbsenceFactory extends Factory
{
    protected $model = ESBTPAbsence::class;

    public function definition()
    {
        $estJustifiee = $this->faker->boolean;
        return [
            'etudiant_id' => ESBTPEtudiant::factory(),
            'matiere_id' => ESBTPMatiere::factory(),
            'date_absence' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'nombre_heures' => $this->faker->numberBetween(1, 8),
            'motif' => $estJustifiee ? $this->faker->randomElement([
                'Maladie',
                'Rendez-vous médical',
                'Problème familial',
                'Problème de transport',
                'Stage'
            ]) : null,
            'est_justifiee' => $estJustifiee,
            'justification' => $estJustifiee ? $this->faker->sentence : null,
            'date_justification' => $estJustifiee ? $this->faker->dateTimeBetween('-5 months', 'now') : null,
            'periode' => $this->faker->randomElement(['semestre1', 'semestre2']),
            'annee_universitaire_id' => 1,
            // Premier utilisateur existant, nul sur une base vide : un 1 en dur viole
            // la clé étrangère vers users dès qu'aucun compte n'a été créé.
            'created_by' => fn () => \App\Models\User::query()->min('id'),
            'updated_by' => fn () => \App\Models\User::query()->min('id'),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
