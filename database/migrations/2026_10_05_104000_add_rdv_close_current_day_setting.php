<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KEY = 'inscriptions.rdv.fermer_jour_a_minuit';

    public function up(): void
    {
        if (DB::table('settings')->where('key', self::KEY)->exists()) {
            return;
        }

        $createur = DB::table('users')->min('id');
        $maintenant = now();

        DB::table('settings')->insert([
            'key' => self::KEY,
            'value' => '0',
            'type' => 'boolean',
            'group' => 'scolarite',
            'category' => 'scolarite',
            'default_value' => '0',
            'description' => "Ferme automatiquement les créneaux du jour dès 00:00. Les rendez-vous déjà réservés restent conservés, mais aucune nouvelle réservation, affectation ou reprogrammation ne peut être ajoutée sur la date du jour.",
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'sort_order' => 194,
            'created_by' => $createur,
            'updated_by' => $createur,
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', self::KEY)->delete();
    }
};
