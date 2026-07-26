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
        Schema::create('esbtp_lmd_credit_wallet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->cascadeOnDelete();
            $table->foreignId('bulletin_id')->nullable()->constrained('esbtp_lmd_bulletins')->nullOnDelete();
            $table->string('source_type', 64);
            $table->unsignedBigInteger('source_id');
            $table->string('source_fingerprint', 64);
            $table->string('event_type', 32);
            $table->integer('credit_delta')->default(0);
            $table->integer('credits_expected_delta')->default(0);
            $table->unsignedBigInteger('annee_universitaire_id')->nullable();
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->unsignedBigInteger('parcours_id')->nullable();
            $table->unsignedTinyInteger('semestre')->nullable();
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->string('decision', 64)->nullable();
            $table->dateTime('source_published_at')->nullable();
            $table->json('source_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id', 'source_fingerprint'], 'lmd_credit_wallet_source_state_unique');
            $table->index(['etudiant_id', 'created_at'], 'lmd_credit_wallet_student_idx');
            $table->index(['source_type', 'source_id'], 'lmd_credit_wallet_source_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_lmd_credit_wallet_entries');
    }
};