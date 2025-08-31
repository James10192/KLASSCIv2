<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPFraisCategory;

class ESBTPFraisCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Supprimer les règles existantes d'abord
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('esbtp_frais_rules')->truncate();
        \DB::table('esbtp_frais_categories')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Créer les catégories obligatoires par défaut
        $mandatoryCategories = ESBTPFraisCategory::getDefaultMandatoryCategories();
        foreach ($mandatoryCategories as $category) {
            ESBTPFraisCategory::create($category);
        }

        // Créer les catégories optionnelles par défaut
        $optionalCategories = ESBTPFraisCategory::getDefaultOptionalCategories();
        foreach ($optionalCategories as $category) {
            ESBTPFraisCategory::create($category);
        }

        $this->command->info('Catégories de frais créées avec succès:');
        $this->command->table(
            ['Nom', 'Code', 'Type', 'Montant par défaut'],
            ESBTPFraisCategory::all()->map(function ($category) {
                return [
                    $category->name,
                    $category->code,
                    $category->is_mandatory ? 'Obligatoire' : 'Optionnel',
                    number_format($category->default_amount, 0, ',', ' ') . ' FCFA'
                ];
            })->toArray()
        );
    }
}