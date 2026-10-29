<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('esbtp_bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
            $table->foreignId('classe_id')->constrained('esbtp_classes')->onDelete('cascade');
            $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->onDelete('cascade');
            $table->enum('periode', ['semestre1', 'semestre2', 'annuel'])->default('semestre1');
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->integer('rang')->nullable();
            $table->integer('effectif_classe')->nullable();
            $table->text('appreciation_generale')->nullable();
            $table->string('decision_conseil')->nullable();
            $table->string('mention')->nullable();
            $table->boolean('signature_directeur')->default(false);
            $table->boolean('signature_responsable')->default(false);
            $table->boolean('signature_parent')->default(false);
            $table->timestamp('date_signature_directeur')->nullable();
            $table->timestamp('date_signature_responsable')->nullable();
            $table->timestamp('date_signature_parent')->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Ensure uniqueness for student, class, academic year, and period
            $table->unique(['etudiant_id', 'classe_id', 'annee_universitaire_id', 'periode'], 'bulletin_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_bulletins');
    }
};
