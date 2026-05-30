<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_inscription_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained('esbtp_inscriptions')->cascadeOnDelete();
            $table->string('type_phase', 32);
            $table->foreignId('classe_id')->constrained('esbtp_classes')->cascadeOnDelete();
            $table->foreignId('filiere_id')->nullable()->constrained('esbtp_filieres')->nullOnDelete();
            $table->unsignedTinyInteger('semestre_debut')->default(1);
            $table->unsignedTinyInteger('semestre_fin')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('orientation_target_id')->nullable();
            $table->timestamp('date_activation')->nullable();
            $table->timestamp('date_cloture')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inscription_id', 'type_phase']);
            $table->index(['inscription_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_inscription_phases');
    }
};
