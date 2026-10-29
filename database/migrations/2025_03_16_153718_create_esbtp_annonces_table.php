<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpAnnoncesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Création de la table principale des annonces
        Schema::create('esbtp_annonces', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('contenu');
            $table->enum('type', ['general', 'classe', 'etudiant'])->default('general');
            $table->datetime('date_publication');
            $table->datetime('date_expiration')->nullable();
            $table->tinyInteger('priorite')->default(0); // 0: normale, 1: moyenne, 2: élevée
            $table->boolean('is_published')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Clés étrangères
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Table pivot pour la relation avec les classes
        Schema::create('esbtp_annonce_classe', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('annonce_id');
            $table->unsignedBigInteger('classe_id');
            $table->timestamps();

            // Clés étrangères
            $table->foreign('annonce_id')->references('id')->on('esbtp_annonces')->onDelete('cascade');
            $table->foreign('classe_id')->references('id')->on('esbtp_classes')->onDelete('cascade');

            // Index unique pour éviter les doublons
            $table->unique(['annonce_id', 'classe_id']);
        });

        // Table pivot pour la relation avec les étudiants
        Schema::create('esbtp_annonce_etudiant', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('annonce_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->boolean('is_read')->default(false);
            $table->datetime('read_at')->nullable();
            $table->timestamps();

            // Clés étrangères
            $table->foreign('annonce_id')->references('id')->on('esbtp_annonces')->onDelete('cascade');
            $table->foreign('etudiant_id')->references('id')->on('esbtp_etudiants')->onDelete('cascade');

            // Index unique pour éviter les doublons
            $table->unique(['annonce_id', 'etudiant_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Supprimer les tables dans l'ordre inverse de leur création
        Schema::dropIfExists('esbtp_annonce_etudiant');
        Schema::dropIfExists('esbtp_annonce_classe');
        Schema::dropIfExists('esbtp_annonces');
    }
}
