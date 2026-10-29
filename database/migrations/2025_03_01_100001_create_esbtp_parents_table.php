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
        // Vérifier si la table esbtp_parents existe déjà
        $parentsTableExists = Schema::hasTable('esbtp_parents');
        
        // Si la table n'existe pas, la créer
        if (!$parentsTableExists) {
            Schema::create('esbtp_parents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('nom');
                $table->string('prenoms');
                $table->enum('sexe', ['M', 'F']);
                $table->string('profession')->nullable();
                $table->text('adresse')->nullable();
                $table->string('telephone');
                $table->string('telephone_secondaire')->nullable();
                $table->string('email')->nullable();
                $table->string('type_piece_identite')->nullable()->comment('CNI, Passeport, etc.');
                $table->string('numero_piece_identite')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
            });
            
            // Journalisation de la création de la table
            \Log::info('Table esbtp_parents créée avec succès.');
        } else {
            // Journalisation de l'existence de la table
            \Log::info('La table esbtp_parents existe déjà.');
        }

        // Vérifier si la table pivot existe déjà
        $pivotTableExists = Schema::hasTable('esbtp_etudiant_parent');
        
        // Si la table pivot n'existe pas, la créer
        if (!$pivotTableExists) {
            // Table pivot pour la relation entre étudiants et parents
            Schema::create('esbtp_etudiant_parent', function (Blueprint $table) {
                $table->id();
                $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
                $table->foreignId('parent_id')->constrained('esbtp_parents')->onDelete('cascade');
                $table->string('relation')->comment('père, mère, tuteur, etc.');
                $table->boolean('is_tuteur')->default(false);
                $table->timestamps();

                $table->unique(['etudiant_id', 'parent_id', 'relation']);
            });
            
            // Journalisation de la création de la table pivot
            \Log::info('Table pivot esbtp_etudiant_parent créée avec succès.');
        } else {
            // Journalisation de l'existence de la table pivot
            \Log::info('La table pivot esbtp_etudiant_parent existe déjà.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_etudiant_parent');
        Schema::dropIfExists('esbtp_parents');
    }
}; 