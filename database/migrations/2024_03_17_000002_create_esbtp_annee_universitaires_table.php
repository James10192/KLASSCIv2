<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateESBTPAnneeUniversitairesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Vérifier si la table existe déjà
        $tableExists = Schema::hasTable('esbtp_annee_universitaires');
        
        // Si la table n'existe pas, la créer
        if (!$tableExists) {
            Schema::create('esbtp_annee_universitaires', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Nom de l'année universitaire (ex: 2024-2025)
                $table->date('start_date'); // Date de début
                $table->date('end_date'); // Date de fin
                $table->boolean('is_current')->default(false); // Indique si c'est l'année universitaire en cours
                $table->boolean('is_active')->default(true); // Statut actif/inactif
                $table->text('description')->nullable(); // Description
                $table->timestamps();
                $table->softDeletes(); // Pour la suppression logique
            });
            
            // Journalisation de la création de la table
            \Log::info('Table esbtp_annee_universitaires créée avec succès.');
        } else {
            // Journalisation de l'existence de la table
            \Log::info('La table esbtp_annee_universitaires existe déjà.');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_annee_universitaires');
    }
}
