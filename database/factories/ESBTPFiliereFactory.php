<?php

namespace Database\Factories;

use App\Models\ESBTPFiliere;
use Illuminate\Database\Eloquent\Factories\Factory;

class ESBTPFiliereFactory extends Factory
{
    protected $model = ESBTPFiliere::class;

    public function definition(): array
    {
        $name = $this->faker->randomElement(['BTP', 'Comptabilité', 'Informatique', 'Marketing', 'Logistique']);

        return [
            'name' => $name . ' ' . $this->faker->numberBetween(1, 999),
            'code' => strtoupper(substr($name, 0, 3)) . $this->faker->unique()->numberBetween(100, 9999),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'is_tronc_commun' => false,
        ];
    }
}
