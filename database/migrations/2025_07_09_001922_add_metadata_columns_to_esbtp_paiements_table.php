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
            // Référence externe pour les paiements - ajouter seulement si n'existe pas
            if (!Schema::hasColumn('esbtp_paiements', 'reference_externe')) {
                $table->string('reference_externe', 100)->nullable()->after('reference_paiement');
            }

            // Métadonnées JSON - ajouter seulement si n'existe pas
            if (!Schema::hasColumn('esbtp_paiements', 'metadata')) {
                $table->json('metadata')->nullable()->after('reference_externe');
            }

            // Référence vers la relance - ajouter seulement si n'existe pas
            if (!Schema::hasColumn('esbtp_paiements', 'relance_id')) {
                $table->unsignedBigInteger('relance_id')->nullable()->after('metadata');
            }

            // Clé étrangère pour relance_id - déjà créée par la migration précédente
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
