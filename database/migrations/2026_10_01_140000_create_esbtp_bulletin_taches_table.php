<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi des travaux longs sur les bulletins : génération d'une classe entière
 * et PDF groupé.
 *
 * Jusqu'ici, c'était l'onglet qui enchaînait les tranches : le quitter
 * arrêtait le travail. La liste à traiter et l'avancement vivent désormais en
 * base, et la planification reprend là où l'onglet s'est arrêté.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_bulletin_taches', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);                 // generation | export
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id')->nullable();
            $table->string('periode', 30)->nullable();
            $table->string('statut', 20)->default('en_attente');
            $table->json('parametres')->nullable();     // recalculer, mode, entête…
            $table->json('elements');                   // identifiants figés au lancement
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->json('resultat')->nullable();       // cumul des tranches
            $table->string('message', 500)->nullable();
            $table->string('fichier', 255)->nullable();
            $table->unsignedTinyInteger('reprises')->default(0);
            // Une tranche qui tue le processus (mémoire, limite de l'hébergeur)
            // ne passe par aucun catch : le compteur est écrit AVANT l'essai.
            $table->unsignedInteger('position_essayee')->nullable();
            $table->unsignedTinyInteger('essais_position')->default(0);
            $table->timestamp('demarree_at')->nullable();
            $table->timestamp('terminee_at')->nullable();
            $table->timestamp('cloche_at')->nullable();     // notification dans l'application
            $table->timestamp('notifiee_at')->nullable();   // e-mail tenté, ou rendu inutile par une vue
            $table->timestamp('vue_at')->nullable();
            $table->timestamp('email_envoye_at')->nullable();
            $table->string('email_erreur', 255)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'statut']);
            $table->index('statut');
        });

        // Une seconde personne qui lance le même travail rejoint la tâche en
        // cours au lieu d'en créer une concurrente ; elle est prévenue elle aussi.
        Schema::create('esbtp_bulletin_tache_abonnes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tache_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('vue_at')->nullable();
            $table->timestamps();

            $table->foreign('tache_id')->references('id')->on('esbtp_bulletin_taches')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['tache_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_bulletin_tache_abonnes');
        Schema::dropIfExists('esbtp_bulletin_taches');
    }
};
