<?php

namespace Database\Factories;

use App\Models\ESBTPFraisCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Une categorie de frais, pour les tests.
 *
 * `code` est unique en base : on le derive d'une sequence plutot que du faker,
 * pour qu'un test qui cree dix categories ne tombe pas sur une collision une fois
 * sur cent — le genre d'echec intermittent qui fait douter du test au lieu du code.
 *
 * `sort_order` porte l'ordre dans lequel un versement solde les frais. Il est
 * explicite dans les tests qui en dependent, jamais laisse au hasard.
 */
class ESBTPFraisCategoryFactory extends Factory
{
    protected $model = ESBTPFraisCategory::class;

    private static int $sequence = 0;

    public function definition(): array
    {
        $n = ++self::$sequence;

        return [
            'name' => 'Frais test '.$n,
            'code' => 'TEST_'.strtoupper(uniqid()).'_'.$n,
            'is_mandatory' => true,
            'accepts_in_kind' => false,
            'is_active' => true,
            'category_type' => 'academic',
            'sort_order' => $n,
            'default_amount' => 0,
            'payment_deadline_days' => 30,
        ];
    }

    /**
     * L'ordre de service voulu par l'ecole : c'est lui qui decide quel frais un
     * versement solde en premier.
     */
    public function ordre(int $rang): static
    {
        return $this->state(fn () => ['sort_order' => $rang]);
    }

    public function optionnelle(): static
    {
        return $this->state(fn () => ['is_mandatory' => false]);
    }
}
