<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_lmd_parcours_ue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcours_id')->constrained('esbtp_lmd_parcours')->cascadeOnDelete();
            $table->foreignId('unite_enseignement_id')->constrained('esbtp_unites_enseignement')->cascadeOnDelete();
            $table->unsignedTinyInteger('semestre');         // Dans quel semestre cette UE apparait
            $table->boolean('is_optional')->default(false);  // UE optionnelle
            $table->timestamps();

            $table->unique(['parcours_id', 'unite_enseignement_id', 'semestre'], 'parcours_ue_semestre_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_lmd_parcours_ue');
    }
};
