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
        Schema::create('esbtp_academic_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('type', 64);
            $table->string('severity', 20)->default('warning');
            $table->string('status', 24)->default('open');
            $table->foreignId('annee_universitaire_id')->nullable()
                ->constrained('esbtp_annee_universitaires')->nullOnDelete();
            $table->string('semester', 20)->nullable();
            $table->foreignId('classe_id')->nullable()->constrained('esbtp_classes')->nullOnDelete();
            $table->foreignId('etudiant_id')->nullable()->constrained('esbtp_etudiants')->nullOnDelete();
            $table->foreignId('matiere_id')->nullable()->constrained('esbtp_matieres')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('esbtp_teachers')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type', 120)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('message');
            $table->text('recommended_action')->nullable();
            $table->json('metadata')->nullable();
            $table->string('source_version', 24)->default('1');
            $table->dateTime('detected_at');
            $table->dateTime('last_seen_at');
            $table->dateTime('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('dismissed_at')->nullable();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['annee_universitaire_id', 'semester', 'status', 'severity'],
                'eaa_scope_status_idx'
            );
            $table->index(['classe_id', 'status', 'severity'], 'eaa_class_status_idx');
            $table->index(['assignee_id', 'status', 'severity'], 'eaa_assignee_status_idx');
            $table->index(['entity_type', 'entity_id'], 'eaa_entity_idx');
            $table->index(['type', 'status', 'last_seen_at'], 'eaa_type_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_academic_alerts');
    }
};
