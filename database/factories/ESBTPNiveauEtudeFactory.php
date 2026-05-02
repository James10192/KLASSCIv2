<?php

namespace Database\Factories;

use App\Models\ESBTPNiveauEtude;
use Illuminate\Database\Eloquent\Factories\Factory;

class ESBTPNiveauEtudeFactory extends Factory
{
    protected $model = ESBTPNiveauEtude::class;

    public function definition(): array
    {
        $year = $this->faker->numberBetween(1, 3);

        return [
            'name' => 'BTS ' . $year,
            'libelle' => 'BTS Année ' . $year,
            'code' => 'BTS' . $year . $this->faker->unique()->randomNumber(4),
            'type' => 'BTS',
            'year' => $year,
            'description' => 'Niveau BTS ' . $year,
            'is_active' => true,
        ];
    }
}
