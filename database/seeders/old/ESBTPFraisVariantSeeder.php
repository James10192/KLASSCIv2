<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisVariant;

class ESBTPFraisVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Nettoyer les variants existants
        ESBTPFraisVariant::truncate();

        // Récupérer les catégories
        $cantine = ESBTPFraisCategory::where('code', 'CANTINE')->first();
        $transport = ESBTPFraisCategory::where('code', 'TRANSPORT')->first();

        if ($cantine) {
            // Variants pour la cantine
            ESBTPFraisVariant::create([
                'frais_category_id' => $cantine->id,
                'name' => 'Menu Standard',
                'description' => 'Menu quotidien avec plat principal, accompagnement et boisson',
                'amount' => 30000,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 1,
                'additional_data' => [
                    'includes' => ['plat_principal', 'accompagnement', 'boisson'],
                    'frequency' => 'monthly'
                ]
            ]);

            ESBTPFraisVariant::create([
                'frais_category_id' => $cantine->id,
                'name' => 'Menu Premium',
                'description' => 'Menu complet avec entrée, plat, dessert et boisson',
                'amount' => 45000,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 2,
                'additional_data' => [
                    'includes' => ['entree', 'plat_principal', 'accompagnement', 'dessert', 'boisson'],
                    'frequency' => 'monthly'
                ]
            ]);

            ESBTPFraisVariant::create([
                'frais_category_id' => $cantine->id,
                'name' => 'Menu Léger',
                'description' => 'Menu allégé avec salade et boisson',
                'amount' => 20000,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 3,
                'additional_data' => [
                    'includes' => ['salade', 'boisson'],
                    'frequency' => 'monthly'
                ]
            ]);
        }

        if ($transport) {
            // Variants pour le transport
            ESBTPFraisVariant::create([
                'frais_category_id' => $transport->id,
                'name' => 'Arrêt Centre-ville',
                'description' => 'Transport depuis/vers le centre-ville d\'Abidjan',
                'amount' => 25000,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 1,
                'additional_data' => [
                    'zone' => 'centre_ville',
                    'distance_km' => 15,
                    'duration_minutes' => 30,
                    'stops' => ['Plateau', 'Cocody', 'Marcory']
                ]
            ]);

            ESBTPFraisVariant::create([
                'frais_category_id' => $transport->id,
                'name' => 'Arrêt Yopougon',
                'description' => 'Transport depuis/vers Yopougon',
                'amount' => 30000,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 2,
                'additional_data' => [
                    'zone' => 'yopougon',
                    'distance_km' => 20,
                    'duration_minutes' => 45,
                    'stops' => ['Yop Sicogi', 'Yop Siporex', 'Banco']
                ]
            ]);

            ESBTPFraisVariant::create([
                'frais_category_id' => $transport->id,
                'name' => 'Arrêt Abobo',
                'description' => 'Transport depuis/vers Abobo',
                'amount' => 35000,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 3,
                'additional_data' => [
                    'zone' => 'abobo',
                    'distance_km' => 25,
                    'duration_minutes' => 50,
                    'stops' => ['Abobo Gare', 'Abobo Baoulé', 'Anyama']
                ]
            ]);

            ESBTPFraisVariant::create([
                'frais_category_id' => $transport->id,
                'name' => 'Arrêt Banlieue',
                'description' => 'Transport depuis/vers la banlieue éloignée',
                'amount' => 40000,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 4,
                'additional_data' => [
                    'zone' => 'banlieue',
                    'distance_km' => 35,
                    'duration_minutes' => 60,
                    'stops' => ['Bingerville', 'Songon', 'Dabou']
                ]
            ]);
        }

        $this->command->info('Variants logiques créés avec succès:');
        
        // Afficher un résumé
        $cantineVariants = ESBTPFraisVariant::where('frais_category_id', $cantine?->id)->count();
        $transportVariants = ESBTPFraisVariant::where('frais_category_id', $transport?->id)->count();
        
        $this->command->table(
            ['Catégorie', 'Nombre de variants'],
            [
                ['Cantine', $cantineVariants],
                ['Transport', $transportVariants],
                ['Total', $cantineVariants + $transportVariants]
            ]
        );
    }
}
