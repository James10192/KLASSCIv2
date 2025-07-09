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
            // Référence externe pour les paiements (ex: référence banque, mobile money, etc.)
            $table->string('reference_externe', 100)->nullable()->after('reference_paiement');

            // Métadonnées JSON pour stocker des informations supplémentaires
            $table->json('metadata')->nullable()->after('reference_externe');

            // Référence vers la relance qui a généré ce paiement
            $table->unsignedBigInteger('relance_id')->nullable()->after('metadata');

            // Clé étrangère pour relance_id
            $table->foreign('relance_id')->references('id')->on('esbtp_relances')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->dropForeign(['relance_id']);
            $table->dropColumn([
                'reference_externe',
                'metadata',
                'relance_id'
            ]);
        });
    }
};
