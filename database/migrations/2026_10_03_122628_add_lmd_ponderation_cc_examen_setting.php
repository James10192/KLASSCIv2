<?php

use App\Models\Setting;
use App\Services\LMD\LmdAcademicRuleProfile;
use Illuminate\Database\Migrations\Migration;

/**
 * La bascule qui fait entrer la pondération contrôle continu / examen dans la
 * moyenne d'un ECUE. Désactivée : rien ne change pour une école qui ne l'active
 * pas. Une valeur déjà posée n'est pas écrasée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => LmdAcademicRuleProfile::REGLAGE_PONDERATION_ACTIVE],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'lmd',
                'category' => 'evaluations',
                'description' => 'Appliquer la pondération contrôle continu / examen à la moyenne des ECUE',
                'default_value' => '0',
                'sort_order' => 19,
                'is_active' => true,
                'is_required' => false,
            ]
        );
    }

    public function down(): void
    {
        Setting::where('key', LmdAcademicRuleProfile::REGLAGE_PONDERATION_ACTIVE)->delete();
    }
};
