<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table d'audit pour les recalculs automatiques de résultats par matière.
 *
 * Chaque ligne représente un recalcul déclenché par :
 *  - source = 'observer' : sauvegarde/suppression d'une note (ESBTPNoteObserver)
 *  - source = 'command'  : commande artisan notes:recompute
 *  - source = 'manual'   : régénération manuelle d'un bulletin
 *
 * Permet de répondre à « quand cette moyenne a-t-elle changé et qui l'a déclenché ? »
 * sans devoir parcourir l'audit `audits` global (volumineux).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_resultats_recompute_log', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('matiere_id');
            $table->string('periode', 32)->comment('semestre1 | semestre2 | annuel');
            $table->unsignedBigInteger('annee_universitaire_id');

            // Snapshot avant/après pour audit
            $table->decimal('moyenne_avant', 5, 2)->nullable();
            $table->decimal('moyenne_apres', 5, 2);

            $table->enum('source', ['observer', 'command', 'manual'])
                ->default('observer')
                ->comment('Origine du recalcul');

            $table->unsignedBigInteger('triggered_by')->nullable()
                ->comment('user_id qui a causé le recalcul (null si CLI/queue sans contexte)');

            $table->timestamp('recomputed_at')->useCurrent();
            $table->timestamps();

            // Index pour les requêtes d'audit fréquentes
            $table->index(['etudiant_id', 'classe_id', 'periode'], 'recompute_log_student_period_idx');
            $table->index(['matiere_id', 'recomputed_at'], 'recompute_log_matiere_time_idx');
            $table->index('source', 'recompute_log_source_idx');
            $table->index('recomputed_at', 'recompute_log_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_resultats_recompute_log');
    }
};
