<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 19 Plan v4 — index composites pour analytics WhatsApp/SMS.
 *
 * Optimise les queries du WhatsAppMetricsService + dashboard cost qui agrègent
 * par (channel, created_at) et (status, created_at, cost_fcfa).
 *
 * Sans ces index, les queries scanaient 100k+ rows sur les tenants à fort volume.
 * Avec : index seek O(log n).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('parent_notification_logs', function (Blueprint $table) {
            // Index pour analytics coût mensuel par canal
            // Query optimisée : SELECT SUM(cost_fcfa), COUNT(*) FROM parent_notification_logs
            //   WHERE channel = ? AND created_at >= ? AND created_at < ?
            $table->index(['channel', 'created_at'], 'pnl_channel_created_idx');

            // Index pour dashboard delivery rate (status = 'sent'/'delivered'/'failed' par jour)
            $table->index(['status', 'created_at'], 'pnl_status_created_idx');

            // Index pour drill-down par étudiant et période (parent fiche détail)
            $table->index(['etudiant_id', 'created_at'], 'pnl_etudiant_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('parent_notification_logs', function (Blueprint $table) {
            $table->dropIndex('pnl_channel_created_idx');
            $table->dropIndex('pnl_status_created_idx');
            $table->dropIndex('pnl_etudiant_created_idx');
        });
    }
};
