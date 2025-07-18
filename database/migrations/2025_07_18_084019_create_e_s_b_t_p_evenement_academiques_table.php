<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateESBTPEvenementAcademiquesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_evenements_academiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->onDelete('cascade');
            $table->string('titre');
            $table->text('description');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('type', [
                'rentree', 'orientation', 'examens', 'vacances', 'reprise', 
                'soutenances', 'ceremonie', 'fermeture', 'stage', 'reunion',
                'formation', 'conference', 'autre'
            ]);
            $table->string('icone', 50)->default('calendar'); // FontAwesome icon name
            $table->string('couleur', 20)->default('primary'); // Bootstrap color class
            $table->boolean('afficher_calendrier')->default(true);
            $table->boolean('afficher_timeline')->default(true);
            $table->boolean('notification_active')->default(false);
            $table->integer('jours_notification')->default(7); // Notifications X jours avant
            $table->text('notes')->nullable();
            $table->json('participants')->nullable(); // JSON: filières, niveaux concernés
            $table->string('lieu')->nullable();
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->enum('statut', ['planifie', 'confirme', 'annule', 'reporte', 'termine'])->default('planifie');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour optimiser les requêtes
            $table->index(['annee_universitaire_id', 'date_debut'], 'evt_annee_date_idx');
            $table->index(['type', 'statut'], 'evt_type_statut_idx');
            $table->index(['afficher_calendrier', 'is_active'], 'evt_calendrier_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_evenements_academiques');
    }
}
