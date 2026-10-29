<?php

namespace Database\Factories;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Ce qu'un etudiant doit sur un frais donne.
 *
 * `amount` n'a pas de defaut aleatoire : c'est le du, et un du tire au hasard
 * rendrait illisible tout test qui verifie une repartition. Chaque test dit ce
 * qu'il doit.
 */
class ESBTPFraisSubscriptionFactory extends Factory
{
    protected $model = ESBTPFraisSubscription::class;

    public function definition(): array
    {
        return [
            'inscription_id' => ESBTPInscription::factory(),
            'frais_category_id' => ESBTPFraisCategory::factory(),
            'amount' => 0,
            'is_active' => true,
            'satisfied_in_kind' => false,
            'subscribed_at' => now(),
            // NOT NULL sans defaut en base : une souscription dit toujours QUI a
            // engage l'etudiant sur ce frais.
            'created_by' => User::factory(),
        ];
    }

    /**
     * Le frais a ete depose en nature : il ne reclame plus d'argent.
     */
    public function enNature(): static
    {
        return $this->state(fn () => [
            'satisfied_in_kind' => true,
            'deposited_at' => now(),
        ]);
    }
}
