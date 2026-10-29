<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_family_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('esbtp_parents')->cascadeOnDelete();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->cascadeOnDelete();
            $table->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('verified_at');
            $table->timestamp('student_consent_at')->nullable();
            $table->string('evidence_type', 32);
            $table->string('evidence_hash', 64);
            $table->string('consent_hash', 64)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['parent_id', 'etudiant_id'], 'family_grant_parent_student_unq');
            $table->index(['parent_id', 'revoked_at', 'expires_at'], 'family_grant_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_family_access_grants');
    }
};
