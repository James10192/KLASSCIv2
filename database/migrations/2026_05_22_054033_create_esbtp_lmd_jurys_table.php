<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_lmd_jurys', function (Blueprint $table) {
            $table->id();

            $table->foreignId('annee_universitaire_id')
                ->constrained('esbtp_annee_universitaires')
                ->cascadeOnDelete();
            $table->foreignId('session_id')
                ->nullable()
                ->constrained('esbtp_lmd_sessions')
                ->nullOnDelete();
            $table->foreignId('parcours_id')
                ->nullable()
                ->constrained('esbtp_lmd_parcours')
                ->nullOnDelete();
            $table->foreignId('classe_id')
                ->nullable()
                ->constrained('esbtp_classes')
                ->nullOnDelete();
            $table->unsignedTinyInteger('semestre')->nullable();

            $table->string('libelle');
            $table->date('date_jury')->nullable();

            // PV (Procès-Verbal) légal
            $table->string('pv_numero', 64)->nullable()->unique()
                ->comment('PV-{ANNEE}-{TENANT}-{SEQ4} — thread-safe via DB lock');
            $table->string('pv_path')->nullable()
                ->comment('Path relatif storage/pv/{tenant}/{annee}/{numero}.pdf');
            $table->dateTime('pv_genere_at')->nullable();
            $table->foreignId('pv_genere_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Workflow state machine
            $table->string('status', 32)->default('preparation')
                ->comment('preparation|en_cours|clos|publie|archive');
            $table->dateTime('clos_at')->nullable();
            $table->dateTime('publie_at')->nullable();
            $table->foreignId('publie_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('observations')->nullable();

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

            $table->index(['annee_universitaire_id', 'parcours_id', 'semestre'], 'idx_jurys_scope');
            $table->index(['status'], 'idx_jurys_status');
            $table->index(['pv_genere_at'], 'idx_jurys_pv_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_lmd_jurys');
    }
};
