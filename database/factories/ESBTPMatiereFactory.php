<?php

namespace Database\Factories;

use App\Models\ESBTPMatiere;
use Illuminate\Database\Eloquent\Factories\Factory;

class ESBTPMatiereFactory extends Factory
{
    protected $model = ESBTPMatiere::class;

    public function definition(): array
    {
        $name = $this->faker->randomElement(['Mathématiques', 'Français', 'Anglais', 'Comptabilité', 'Informatique', 'Économie']);

        return [
            'name' => $name,
            'code' => strtoupper(substr($name, 0, 3)) . $this->faker->unique()->numberBetween(100, 9999),
            'description' => $this->faker->sentence(),
            'coefficient' => $this->faker->randomElement([1, 2, 3]),
            'heures_cm' => 20,
            'heures_td' => 10,
            'heures_tp' => 0,
            'is_active' => true,
        ];
    }
}
