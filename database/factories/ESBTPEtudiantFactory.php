<?php

namespace Database\Factories;

use App\Models\ESBTPEtudiant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ESBTPEtudiantFactory extends Factory
{
    protected $model = ESBTPEtudiant::class;

    public function definition(): array
    {
        return [
            'matricule' => 'MAT' . $this->faker->unique()->numberBetween(10000, 9999999),
            'nom' => $this->faker->lastName(),
            'prenoms' => $this->faker->firstName(),
            'sexe' => $this->faker->randomElement(['M', 'F']),
            'date_naissance' => $this->faker->date('Y-m-d', '-18 years'),
            'lieu_naissance' => $this->faker->city(),
            'nationalite' => 'Ivoirienne',
            'adresse' => $this->faker->address(),
            'telephone' => '+225' . $this->faker->numerify('##########'),
            'email' => $this->faker->unique()->safeEmail(),
            'statut' => 'actif',
        ];
    }
}
