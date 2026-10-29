<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_lmd_bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('esbtp_classes')->cascadeOnDelete();
            $table->foreignId('parcours_id')->nullable()->constrained('esbtp_lmd_parcours')->nullOnDelete();
            $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->cascadeOnDelete();
            $table->unsignedTinyInteger('semestre');                     // 1-10
            $table->string('niveau')->nullable();                       // Licence 3, Master 1...
            // Labels denormalises pour historique (ne changent pas si domaine/mention renommes)
            $table->string('domaine_label')->nullable();                // Ex: Sciences et Technologies
            $table->string('mention_label')->nullable();                // Ex: Genie Civil
            $table->string('parcours_label')->nullable();               // Ex: GCV Batiment & Urbanisme
            // Resultats
            $table->decimal('moyenne_generale', 5, 2)->nullable();      // Moyenne ponderee par credits
            $table->unsignedInteger('credits_capitalises')->default(0); // Credits valides (AQ+APC)
            $table->unsignedInteger('credits_totaux')->default(30);     // Credits du semestre (typiquement 30)
            $table->unsignedInteger('rang')->nullable();
            $table->unsignedInteger('effectif')->nullable();
            // Deliberation
            $table->string('decision_deliberation')->nullable();        // Encouragement, Avertissement...
            $table->text('appreciation')->nullable();
            // Absences
            $table->unsignedInteger('absences_justifiees')->default(0);
            $table->unsignedInteger('absences_non_justifiees')->default(0);
            // Publication
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['etudiant_id', 'classe_id', 'annee_universitaire_id', 'semestre'],
                'lmd_bulletin_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_lmd_bulletins');
    }
};
