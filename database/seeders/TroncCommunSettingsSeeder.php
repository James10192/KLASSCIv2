<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\SettingsHelper;

class TroncCommunSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'tronc_commun_enabled',
                'value' => false,
                'group' => 'academic',
                'type' => 'boolean',
                'description' => 'Activer le mode tronc commun / spécialisation pour les filières concernées',
            ],
            [
                'key' => 'tronc_commun_report_notes',
                'value' => true,
                'group' => 'academic',
                'type' => 'boolean',
                'description' => 'Reporter les notes du tronc commun (S1) dans la spécialisation',
            ],
            [
                'key' => 'tronc_commun_report_paiements',
                'value' => true,
                'group' => 'academic',
                'type' => 'boolean',
                'description' => 'Reporter les paiements du tronc commun sur l\'inscription de spécialisation',
            ],
            [
                'key' => 'tronc_commun_mga_include_s1',
                'value' => true,
                'group' => 'academic',
                'type' => 'boolean',
                'description' => 'Apres specialisation, inclure le S1 origine avec le S2 specialite dans la moyenne et le rang annuels',
            ],
            [
                'key' => 'tronc_commun_bulletin_show_origin',
                'value' => true,
                'group' => 'academic',
                'type' => 'boolean',
                'description' => 'Afficher la classe d\'origine (tronc commun) sur le bulletin de spécialisation',
            ],
            [
                'key' => 'tronc_commun_matieres_communes',
                'value' => true,
                'group' => 'academic',
                'type' => 'boolean',
                'description' => 'Reporter automatiquement les notes des matières communes entre TC et spécialisation',
            ],
            [
                'key' => 'tronc_commun_planning_semestre_strict',
                'value' => false,
                'group' => 'academic',
                'type' => 'boolean',
                'description' => 'Restreindre le planning : matières TC uniquement en S1, matières spécialisation uniquement en S2',
            ],
        ];

        foreach ($settings as $setting) {
            SettingsHelper::setOrCreate(
                $setting['key'],
                $setting['value'],
                $setting['group'],
                $setting['type']
            );
        }
    }
}
