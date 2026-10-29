<?php

namespace Database\Factories;

use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Database\Eloquent\Factories\Factory;

class ESBTPAnneeUniversitaireFactory extends Factory
{
    protected $model = ESBTPAnneeUniversitaire::class;

    public function definition(): array
    {
        $year = $this->faker->numberBetween(2020, 2030);

        return [
            'name' => $year . '-' . ($year + 1),
            'start_date' => $year . '-09-01',
            'end_date' => ($year + 1) . '-07-31',
            'is_current' => false,
            'is_active' => true,
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
