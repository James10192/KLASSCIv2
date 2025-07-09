<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            // Ajout des colonnes pour les relances et métadonnées
            $table->string('reference_externe', 100)->nullable()->after('reference_paiement');
            $table->json('metadata')->nullable()->after('reference_externe');
            $table->foreignId('relance_id')->nullable()->constrained('esbtp_relances')->onDelete('set null');

            $table->index('reference_externe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->dropForeign(['relance_id']);
            $table->dropIndex(['reference_externe']);
            $table->dropColumn([
                'reference_externe',
                'metadata',
                'relance_id'
            ]);
        });
    }
};
