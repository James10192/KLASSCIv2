<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CLE = 'inscriptions.rdv.fermer_jour_a_minuit';

    public function up(): void
    {
        if (DB::table('settings')->where('key', self::CLE)->exists()) {
            return;
        }

        $auteur = DB::table('users')->min('id');
        DB::table('settings')->insert([
            'key' => self::CLE,
            'value' => '0',
            'type' => 'boolean',
            'group' => 'scolarite',
            'category' => 'scolarite',
            'default_value' => '0',
            'description' => "Ferme automatiquement à 00:00 tous les créneaux du jour. Les rendez-vous déjà pris sont conservés, mais aucune nouvelle famille ne peut être ajoutée ce jour-là.",
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'sort_order' => 195,
            'created_by' => $auteur,
            'updated_by' => $auteur,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', self::CLE)->delete();
    }
};
