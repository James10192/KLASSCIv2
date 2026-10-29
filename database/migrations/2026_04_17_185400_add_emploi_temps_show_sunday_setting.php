<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('esbtp_system_settings')
            ->where('key', 'emploi_temps.show_sunday')
            ->exists();

        if (! $exists) {
            DB::table('esbtp_system_settings')->insert([
                'key' => 'emploi_temps.show_sunday',
                'value' => '0',
                'type' => 'boolean',
                'description' => "Afficher la colonne 'Dimanche' dans la grille horaire et les vues emploi du temps. Masquee par defaut.",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('esbtp_system_settings')
            ->where('key', 'emploi_temps.show_sunday')
            ->delete();
    }
};
