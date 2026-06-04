<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PR6 réconciliation : seuil overdue configurable per tenant.
 * Une session ouverte depuis > N jours sans clôture déclenche
 * notification + bandeau sticky + degraded health.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('settings')->where('key', 'comptabilite.reconciliation.overdue_days')->exists()) {
            return;
        }
        DB::table('settings')->insert([
            'key' => 'comptabilite.reconciliation.overdue_days',
            'value' => '2',
            'type' => 'integer',
            'group' => 'comptabilite',
            'category' => 'comptabilite',
            'description' => 'Nombre de jours après ouverture d\'une session avant qu\'elle soit considérée overdue (déclenche notification + bandeau sticky).',
            'is_required' => 1,
            'default_value' => '2',
            'validation_rules' => json_encode(['required', 'integer', 'min:1', 'max:60']),
            'sort_order' => 17,
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'comptabilite.reconciliation.overdue_days')->delete();
    }
};
