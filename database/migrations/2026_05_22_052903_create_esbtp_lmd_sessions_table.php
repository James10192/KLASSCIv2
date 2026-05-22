<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_lmd_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('annee_universitaire_id')
                ->constrained('esbtp_annee_universitaires')
                ->cascadeOnDelete();
            $table->foreignId('parcours_id')
                ->nullable()
                ->constrained('esbtp_lmd_parcours')
                ->nullOnDelete();

            $table->string('type', 16)->default('normale')
                ->comment('normale|rattrapage|extra');
            $table->unsignedBigInteger('parent_session_id')->nullable()
                ->comment('FK self-ref vers session normale parent');
            $table->unsignedTinyInteger('semestre')->nullable();

            $table->string('libelle');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();

            $table->string('status', 24)->default('draft')
                ->comment('draft|planned|in_progress|completed|published|archived');
            $table->dateTime('published_at')->nullable();
            $table->foreignId('published_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

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

            $table->foreign('parent_session_id')
                ->references('id')
                ->on('esbtp_lmd_sessions')
                ->nullOnDelete();

            $table->index(['annee_universitaire_id', 'parcours_id', 'semestre', 'type'], 'idx_sessions_scope');
            $table->index(['status'], 'idx_sessions_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_lmd_sessions');
    }
};
