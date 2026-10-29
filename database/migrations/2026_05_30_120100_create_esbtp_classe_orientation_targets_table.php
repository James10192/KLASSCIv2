<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_classe_orientation_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_classe_id')->constrained('esbtp_classes')->cascadeOnDelete();
            $table->foreignId('target_classe_id')->constrained('esbtp_classes')->cascadeOnDelete();
            $table->unsignedTinyInteger('semestre_activation')->default(2);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['source_classe_id', 'target_classe_id'], 'esbtp_orientation_targets_unique');
            $table->index(['source_classe_id', 'is_active'], 'esbtp_orientation_targets_source_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_classe_orientation_targets');
    }
};
