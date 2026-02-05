<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cette migration crée la table centrale esbtp_periodes pour le système de périodes académiques génériques.
     *
     * Objectif: Remplacer les semestre/periode hardcodés (INT, VARCHAR, ENUM) par un système flexible
     * supportant N périodes par année universitaire (2 semestres, 3 trimestres, etc.).
     *
     * Ancrage: annee_universitaire_id (PAS etablissement_id - chaque tenant = BDD séparée)
     *
     * Champs:
     * - annee_universitaire_id: Année académique (FK)
     * - nom: Nom de la période (ex: "Semestre 1", "Trimestre 1", "Quadrimestre 1")
     * - ordre: Position dans l'année (1, 2, 3, ...) pour tri/affichage
     * - date_debut: Date de début de la période
     * - date_fin: Date de fin de la période
     * - poids: Coefficient pour calcul moyenne annuelle (défaut: 1 = équipondéré)
     * - is_active: Permet de désactiver une période (ex: année incomplète)
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_periodes', function (Blueprint $table) {
            $table->id();

            // Ancrage sur l'année universitaire (multi-tenant via BDD séparées)
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->foreign('annee_universitaire_id')
                  ->references('id')
                  ->on('esbtp_annees_universitaires')
                  ->onDelete('cascade');

            // Informations de la période
            $table->string('nom', 50); // "Semestre 1", "Trimestre 2", etc.
            $table->tinyInteger('ordre')->unsigned(); // 1, 2, 3, ...

            // Dates de début/fin
            $table->date('date_debut');
            $table->date('date_fin');

            // Poids pour calcul moyenne annuelle
            $table->tinyInteger('poids')->unsigned()->default(1);

            // Statut
            $table->boolean('is_active')->default(true);

            // Timestamps standard
            $table->timestamps();

            // Index pour performance
            $table->index('annee_universitaire_id');
            $table->index('ordre');
            $table->index(['annee_universitaire_id', 'ordre']);

            // Contrainte UNIQUE: Pas de doublon ordre pour une même année
            $table->unique(['annee_universitaire_id', 'ordre'], 'unique_periode_ordre_par_annee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_periodes');
    }
};
