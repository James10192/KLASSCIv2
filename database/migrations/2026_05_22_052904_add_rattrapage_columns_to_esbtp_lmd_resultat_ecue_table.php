<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('esbtp_lmd_resultats_ecues', function (Blueprint $table) {
            $table->decimal('note_session_normale', 5, 2)->nullable()->after('moyenne')
                ->comment('Note session normale (snapshot avant rattrapage)');
            $table->decimal('note_rattrapage', 5, 2)->nullable()->after('note_session_normale')
                ->comment('Note 2e session rattrapage');
            $table->decimal('note_finale', 5, 2)->nullable()->after('note_rattrapage')
                ->comment('Note finale après recalcul max|replace');
            $table->boolean('rattrapage_eligible')->default(false)->after('note_finale')
                ->comment('Calculé : moyenne < seuil_validation_ecue');
            $table->boolean('rattrapage_inscrit')->default(false)->after('rattrapage_eligible')
                ->comment('Étudiant inscrit en 2e session');

            $table->index(['rattrapage_eligible', 'rattrapage_inscrit'], 'idx_ecue_rattrapage');
        });
    }

    public function down()
    {
        Schema::table('esbtp_lmd_resultats_ecues', function (Blueprint $table) {
            $table->dropIndex('idx_ecue_rattrapage');
            $table->dropColumn([
                'note_session_normale',
                'note_rattrapage',
                'note_finale',
                'rattrapage_eligible',
                'rattrapage_inscrit',
            ]);
        });
    }
};
