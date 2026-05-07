<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_student_accessibility_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('etudiant_id')
                ->unique()
                ->constrained('esbtp_etudiants')
                ->onDelete('cascade');

            // Reconnaissance officielle
            $table->boolean('has_official_recognition')->default(false);
            $table->string('recognition_reference', 100)->nullable();

            // Catégories (JSON multi-choix : motrice, visuelle, auditive, cognitive, psychique, dys, chronique, autre)
            $table->json('categories')->nullable();

            // Descriptions
            $table->string('short_description', 200)->nullable();
            $table->text('full_description')->nullable();

            // Aménagements (JSON multi-choix : tiers_temps, salle_adaptee, support_agrandi, interprete_lsf, prise_de_notes, ordinateur_autorise, repos_examen, autre)
            $table->json('accommodations')->nullable();
            $table->text('accommodations_notes')->nullable();

            // Booléens indexés pour filtres rapides
            $table->boolean('requires_third_time')->default(false)->index();
            $table->unsignedSmallInteger('third_time_percentage')->default(33);
            $table->boolean('assistant_required')->default(false)->index();

            // Validité
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            // Audit ownership
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_student_accessibility_profiles');
    }
};
