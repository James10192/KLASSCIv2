<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le statut « redoublant » d'une inscription était posé par le logiciel seul
 * (même niveau que l'année d'avant) et personne ne le confirmait. Le bulletin
 * l'imprimait pourtant.
 *
 * - redoublant_source : d'où vient la valeur — `deduit` (le logiciel),
 *   `confirme` (une personne l'a validée telle quelle), `corrige` (une
 *   personne l'a changée). Nul = jamais établi (inscriptions anciennes, avant
 *   le recensement).
 * - redoublant_confirme_par / _le : qui l'a confirmée ou corrigée, et quand.
 * - redoublant_motif : pourquoi une personne a corrigé la valeur déduite.
 * - decision_reinscription : la décision choisie à la réinscription
 *   (passage, redoublement, rattrapage). Elle ne vivait qu'en tête d'un texte
 *   libre, relu par découpage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('esbtp_inscriptions', 'redoublant_source')) {
                $table->string('redoublant_source', 20)->nullable()->after('is_redoublant');
            }
            if (! Schema::hasColumn('esbtp_inscriptions', 'redoublant_confirme_par')) {
                $table->foreignId('redoublant_confirme_par')->nullable()->after('redoublant_source')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('esbtp_inscriptions', 'redoublant_confirme_le')) {
                $table->timestamp('redoublant_confirme_le')->nullable()->after('redoublant_confirme_par');
            }
            if (! Schema::hasColumn('esbtp_inscriptions', 'redoublant_motif')) {
                $table->text('redoublant_motif')->nullable()->after('redoublant_confirme_le');
            }
            if (! Schema::hasColumn('esbtp_inscriptions', 'decision_reinscription')) {
                $table->string('decision_reinscription', 20)->nullable()->after('redoublant_motif');
            }
        });

        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            // La source d'abord : un index qui commence par la clé étrangère
            // de l'année serait réquisitionné par elle, et down() ne pourrait
            // plus le retirer.
            $table->index(['redoublant_source', 'annee_universitaire_id'], 'insc_redoublant_source_annee_idx');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->dropIndex('insc_redoublant_source_annee_idx');
            if (Schema::hasColumn('esbtp_inscriptions', 'redoublant_confirme_par')) {
                $table->dropConstrainedForeignId('redoublant_confirme_par');
            }
            foreach (['redoublant_source', 'redoublant_confirme_le', 'redoublant_motif', 'decision_reinscription'] as $colonne) {
                if (Schema::hasColumn('esbtp_inscriptions', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
    }
};
