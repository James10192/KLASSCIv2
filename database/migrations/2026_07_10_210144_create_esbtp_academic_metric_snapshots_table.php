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
        Schema::create('esbtp_academic_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('context_hash', 64)->unique();
            $table->string('scope_type', 24);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('academic_system', 12)->nullable();
            $table->foreignId('annee_universitaire_id')->nullable()
                ->constrained('esbtp_annee_universitaires')->nullOnDelete();
            $table->string('semester', 20)->nullable();
            $table->foreignId('classe_id')->nullable()->constrained('esbtp_classes')->nullOnDelete();
            $table->foreignId('etudiant_id')->nullable()->constrained('esbtp_etudiants')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('academic_score', 5, 2)->nullable();
            $table->decimal('operational_score', 5, 2)->nullable();
            $table->unsignedTinyInteger('coverage_pct')->default(0);
            $table->unsignedTinyInteger('confidence_pct')->default(0);
            $table->string('level', 32)->default('insufficient_data');
            $table->json('metrics');
            $table->json('factors')->nullable();
            $table->json('reasons')->nullable();
            $table->string('evidence_hash', 64);
            $table->string('engine_version', 24)->default('1');
            $table->boolean('is_dirty')->default(false);
            $table->dateTime('stale_at')->nullable();
            $table->dateTime('calculated_at');
            $table->timestamps();

            $table->index(
                ['scope_type', 'scope_id', 'annee_universitaire_id', 'semester'],
                'eams_scope_period_idx'
            );
            $table->index(
                ['annee_universitaire_id', 'semester', 'classe_id', 'level'],
                'eams_class_level_idx'
            );
            $table->index(['is_dirty', 'stale_at'], 'eams_dirty_idx');
            $table->index(['user_id', 'calculated_at'], 'eams_user_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_academic_metric_snapshots');
    }
};
