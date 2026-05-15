<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute `responsable_ue_id` à `esbtp_unites_enseignement`.
     *
     * Selon la directive UEMOA 03/2007/CM, chaque Unité d'Enseignement (UE) a
     * un "Responsable de l'UE" (1 par UE) distinct des "Enseignants chargés
     * d'ECUE" (1 par ECUE, stocké sur esbtp_planifications_academiques.enseignant_principal_id).
     *
     * Idempotent multi-instance via Schema::hasColumn (peut être ré-exécuté sur
     * un tenant qui aurait déjà la colonne via une sync précédente).
     *
     * onDelete('set null') : si le user responsable est supprimé du système,
     * on préserve l'UE et on annule juste la responsabilité (l'UE reste valide).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('esbtp_unites_enseignement', 'responsable_ue_id')) {
            Schema::table('esbtp_unites_enseignement', function (Blueprint $table) {
                $table->unsignedBigInteger('responsable_ue_id')->nullable()->after('parcours_id');
                $table->foreign('responsable_ue_id')
                    ->references('id')->on('users')
                    ->onDelete('set null');
                $table->index('responsable_ue_id', 'esbtp_ue_responsable_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('esbtp_unites_enseignement', 'responsable_ue_id')) {
            Schema::table('esbtp_unites_enseignement', function (Blueprint $table) {
                // Drop FK first (sinon dropColumn échoue sur MySQL 8 strict)
                try {
                    $table->dropForeign(['responsable_ue_id']);
                } catch (\Throwable $e) {
                    // FK might already be gone — defensive
                }
                try {
                    $table->dropIndex('esbtp_ue_responsable_idx');
                } catch (\Throwable $e) {
                    // Index might already be gone
                }
                $table->dropColumn('responsable_ue_id');
            });
        }
    }
};
