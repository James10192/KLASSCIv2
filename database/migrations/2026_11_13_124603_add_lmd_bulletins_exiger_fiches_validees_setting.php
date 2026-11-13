<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Réglage d'école : les fiches de notes doivent-elles être validées dans le
 * Pilotage académique avant les bulletins LMD ?
 *
 * Défaut « non » : le bulletin se calcule dès que les notes sont saisies, et
 * l'écran signale seulement les fiches non validées. Le contrôle exigeait le
 * circuit complet, et refusait des classes entières (esbtp-abidjan, novembre
 * 2026 : aucune fiche validée). Une école qui pratique la validation coche la
 * case dans l'onglet LMD des paramètres.
 */
return new class extends Migration
{
    private const CLE = 'lmd_bulletins_exiger_fiches_validees';

    public function up(): void
    {
        if (DB::table('settings')->where('key', self::CLE)->exists()) {
            return;
        }

        DB::table('settings')->insert([
            'key' => self::CLE,
            'value' => '0',
            'type' => 'boolean',
            'group' => 'lmd',
            'category' => 'bulletin',
            'description' => 'Exiger des fiches de notes validées (Pilotage académique) avant de générer les bulletins LMD',
            'is_required' => false,
            'default_value' => '0',
            'is_active' => true,
            'sort_order' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', self::CLE)->delete();
    }
};
