<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes de verrouillage post-réconciliation sur esbtp_paiements.
 * Un paiement avec reconciliation_locked_at != null ne peut plus être modifié
 * (sauf permission comptabilite.reconciliation.bypass_lock).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_paiements', 'reconciliation_locked_at')) {
                $table->timestamp('reconciliation_locked_at')->nullable()->after('validated_by');
            }
            if (!Schema::hasColumn('esbtp_paiements', 'last_reconciliation_session_id')) {
                $table->foreignId('last_reconciliation_session_id')->nullable()->after('reconciliation_locked_at');
                $table->foreign('last_reconciliation_session_id', 'paiements_last_rec_session_fk')
                    ->references('id')->on('reconciliation_sessions')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_paiements', 'last_reconciliation_session_id')) {
                $table->dropForeign('paiements_last_rec_session_fk');
                $table->dropColumn('last_reconciliation_session_id');
            }
            if (Schema::hasColumn('esbtp_paiements', 'reconciliation_locked_at')) {
                $table->dropColumn('reconciliation_locked_at');
            }
        });
    }
};
