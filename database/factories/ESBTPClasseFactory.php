<?php

namespace Database\Factories;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Database\Eloquent\Factories\Factory;

class ESBTPClasseFactory extends Factory
{
    protected $model = ESBTPClasse::class;

    public function definition(): array
    {
        return [
            'name' => 'Classe ' . $this->faker->unique()->numberBetween(1, 99999),
            'code' => 'CL' . $this->faker->unique()->numberBetween(1000, 99999),
            'filiere_id' => ESBTPFiliere::factory(),
            'niveau_etude_id' => ESBTPNiveauEtude::factory(),
            'annee_universitaire_id' => ESBTPAnneeUniversitaire::factory(),
            'places_totales' => 50,
            'places_occupees' => 0,
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'systeme_academique' => 'BTS',
        ];
    }
}
