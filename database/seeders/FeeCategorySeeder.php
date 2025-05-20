<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTP\FeeCategory;

class FeeCategorySeeder extends Seeder
{
    public function run()
    {
        FeeCategory::updateOrCreate([
            'code' => 'INSCR',
            'name' => 'Frais d\'inscription',
        ], [
            'description' => 'Frais d\'inscription obligatoires pour toute nouvelle inscription',
            'is_mandatory' => true,
            'is_active' => true,
        ]);

        FeeCategory::updateOrCreate([
            'code' => 'SCOLA',
            'name' => 'Scolarité annuelle',
        ], [
            'description' => 'Frais de scolarité pour l\'année universitaire',
            'is_mandatory' => true,
            'is_active' => true,
        ]);

        FeeCategory::updateOrCreate([
            'code' => 'CANT',
            'name' => 'Cantine',
        ], [
            'description' => 'Service optionnel de restauration',
            'is_mandatory' => false,
            'is_active' => true,
        ]);

        FeeCategory::updateOrCreate([
            'code' => 'TRANSP',
            'name' => 'Transport',
        ], [
            'description' => 'Service optionnel de transport scolaire',
            'is_mandatory' => false,
            'is_active' => true,
        ]);
    }
}
