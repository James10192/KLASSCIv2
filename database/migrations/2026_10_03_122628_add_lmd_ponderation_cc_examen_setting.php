<?php

use App\Models\Setting;
use App\Services\LMD\LmdAcademicRuleProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La bascule qui fait entrer la pondération contrôle continu / examen dans la
 * moyenne d'un ECUE. Désactivée : rien ne change pour une école qui ne l'active
 * pas. Une valeur déjà posée n'est pas écrasée.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Le premier compte, null sur une base vide : jamais `1` en dur.
        $createur = DB::table('users')->min('id');

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
                'created_by' => $createur,
                'updated_by' => $createur,
            ]
        );
    }

    public function down(): void
    {
        Setting::where('key', LmdAcademicRuleProfile::REGLAGE_PONDERATION_ACTIVE)->delete();
    }
};
