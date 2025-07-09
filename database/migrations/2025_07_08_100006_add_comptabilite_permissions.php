<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'comptabilite.dashboard.view' => 'Voir le dashboard avancé de comptabilité',
            'comptabilite.bons.approve' => 'Approuver les bons de sortie',
            'comptabilite.config.manage' => 'Gérer la configuration comptabilité',
            'comptabilite.reports.export' => 'Exporter les rapports financiers',
            'comptabilite.relances.send' => 'Envoyer les relances de paiement',
            'comptabilite.kpis.view' => 'Voir les KPIs financiers',
            'comptabilite.workflow.manage' => 'Gérer le workflow des dépenses'
        ];

        foreach ($permissions as $name => $description) {
            DB::table('permissions')->insert([
                'name' => $name,
                'guard_name' => 'web',
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'comptabilite.dashboard.view',
            'comptabilite.bons.approve',
            'comptabilite.config.manage',
            'comptabilite.reports.export',
            'comptabilite.relances.send',
            'comptabilite.kpis.view',
            'comptabilite.workflow.manage'
        ];

        DB::table('permissions')->whereIn('name', $permissions)->delete();
    }
};
