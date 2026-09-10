<?php

namespace Database\Factories;

use App\Models\ESBTPResultatMatiere;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPMatiere;
use Illuminate\Database\Eloquent\Factories\Factory;

class ESBTPResultatMatiereFactory extends Factory
{
    protected $model = ESBTPResultatMatiere::class;

    public function definition()
    {
        $moyenne = $this->faker->randomFloat(2, 0, 20);

        return [
            'bulletin_id' => ESBTPBulletin::factory(),
            'matiere_id' => ESBTPMatiere::factory(),
            'moyenne' => $moyenne,
            'statut' => ESBTPResultatMatiere::STATUT_NOTE,
            'coefficient' => $this->faker->numberBetween(1, 4),
            'rang' => $this->faker->numberBetween(1, 50),
            'appreciation' => $this->determinerAppreciation($moyenne),
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /** Une matière dont l'étudiant est dispensé : ni moyenne, ni rang, mais un motif. */
    public function dispensee(string $motif = 'Validée en première année')
    {
        return $this->state(fn () => [
            'moyenne' => null,
            'statut' => ESBTPResultatMatiere::STATUT_DISPENSE,
            'motif_dispense' => $motif,
            'rang' => null,
            'appreciation' => '',
        ]);
    }

    /** Une matière prévue par la maquette dont les notes ne sont pas arrivées. */
    public function nonNotee()
    {
        return $this->state(fn () => [
            'moyenne' => null,
            'statut' => ESBTPResultatMatiere::STATUT_NON_NOTE,
            'rang' => null,
            'appreciation' => '',
        ]);
    }

    private function determinerAppreciation($moyenne)
    {
        if ($moyenne >= 16) {
            return 'Excellent';
        } elseif ($moyenne >= 14) {
            return 'Très Bien';
        } elseif ($moyenne >= 12) {
            return 'Bien';
        } elseif ($moyenne >= 10) {
            return 'Passable';
        } else {
            return 'Insuffisant';
        }
    }
}
