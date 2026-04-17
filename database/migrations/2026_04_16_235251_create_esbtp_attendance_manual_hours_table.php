<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_attendance_manual_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('esbtp_classes')->cascadeOnDelete();
            $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->cascadeOnDelete();
            $table->string('periode', 20);

            $table->decimal('heures_presence', 6, 2)->default(0);
            $table->decimal('heures_absence_justifiees', 6, 2)->default(0);
            $table->decimal('heures_absence_non_justifiees', 6, 2)->default(0);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['etudiant_id', 'matiere_id', 'annee_universitaire_id', 'periode'],
                'manual_hours_unique'
            );
            $table->index(
                ['annee_universitaire_id', 'periode', 'classe_id', 'matiere_id'],
                'manual_hours_bulletin_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_attendance_manual_hours');
    }
};
