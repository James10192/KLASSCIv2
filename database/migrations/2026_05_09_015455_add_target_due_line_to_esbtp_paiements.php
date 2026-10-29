<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute target_due_line_key (nullable) sur esbtp_paiements pour permettre une
     * allocation explicite vers une tranche d'échéancier précise. Nullable +
     * non-FK car les line_keys sont calculés dynamiquement depuis le snapshot
     * (pas de table dédiée stable). Quand non-null, EcheancierPaymentAllocationService
     * priorise cette tranche avant le FIFO catégorie.
     */
    public function up(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_paiements', 'target_due_line_key')) {
                $table->string('target_due_line_key', 200)->nullable()->after('frais_category_id')
                    ->comment("Clé de tranche d'échéancier ciblée (line_key dans le snapshot). Null = allocation FIFO standard.");
                $table->index('target_due_line_key', 'idx_paiements_target_line');
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_paiements', 'target_due_line_key')) {
                $table->dropIndex('idx_paiements_target_line');
                $table->dropColumn('target_due_line_key');
            }
        });
    }
};
