<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_lmd_jury_decisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('jury_id')
                ->constrained('esbtp_lmd_jurys')
                ->cascadeOnDelete();
            $table->foreignId('etudiant_id')
                ->constrained('esbtp_etudiants')
                ->cascadeOnDelete();
            $table->foreignId('bulletin_id')
                ->nullable()
                ->constrained('esbtp_lmd_bulletins')
                ->nullOnDelete();

            // Décision canonique UEMOA
            $table->string('decision_auto', 32)->nullable()
                ->comment('admis|admission_rattrapage|ajourne|exclu|admis_sous_condition|defere');
            $table->string('decision', 32)
                ->comment('Décision finale après override jury éventuel');
            $table->string('mention', 32)->nullable()
                ->comment('passable|assez_bien|bien|tres_bien|excellent');

            // Override jury (motif obligatoire si != auto)
            $table->boolean('override_par_jury')->default(false);
            $table->text('motif_override')->nullable();
            $table->string('vote_resultat', 32)->nullable()
                ->comment('unanime|majorite|partage_voix_president');

            // Scores snapshot
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->unsignedInteger('credits_obtenus')->default(0);
            $table->unsignedInteger('credits_attendus')->default(0);

            // Verrouillage
            $table->boolean('locked')->default(false)
                ->comment('Verrouillé après lock PV — interdit modification');
            $table->dateTime('locked_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jury_id', 'etudiant_id'], 'uniq_jury_etudiant');
            $table->index(['decision'], 'idx_decisions_canonical');
            $table->index(['override_par_jury'], 'idx_decisions_override');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_lmd_jury_decisions');
    }
};
