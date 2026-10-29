<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_examens_planifies', function (Blueprint $table) {
            // La feuille de notes de l'examen : sans ce lien, l'examen ne menait
            // à aucune saisie et son verrou ne protégeait aucune note.
            if (! Schema::hasColumn('esbtp_examens_planifies', 'evaluation_id')) {
                $table->foreignId('evaluation_id')->nullable()->after('matiere_id')
                    ->constrained('esbtp_evaluations')->nullOnDelete();
            }
            // Levée de l'anonymat : datée et nominative, jamais silencieuse.
            if (! Schema::hasColumn('esbtp_examens_planifies', 'anonymat_leve_at')) {
                $table->timestamp('anonymat_leve_at')->nullable()->after('is_anonymous');
                $table->foreignId('anonymat_leve_par')->nullable()->after('anonymat_leve_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_examens_planifies', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_examens_planifies', 'anonymat_leve_par')) {
                $table->dropConstrainedForeignId('anonymat_leve_par');
            }
            if (Schema::hasColumn('esbtp_examens_planifies', 'anonymat_leve_at')) {
                $table->dropColumn('anonymat_leve_at');
            }
            if (Schema::hasColumn('esbtp_examens_planifies', 'evaluation_id')) {
                $table->dropConstrainedForeignId('evaluation_id');
            }
        });
    }
};
