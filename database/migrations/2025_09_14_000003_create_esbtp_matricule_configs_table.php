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
        Schema::create('esbtp_matricule_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('etablissement_id'); // Lié à l'établissement
            $table->string('niveau_etude_code'); // BTS, LICENCE, MASTER, etc.
            $table->string('niveau_etude_name'); // Nom complet pour affichage
            $table->string('pattern'); // Format du matricule ex: {GENRE}{PREFIXE}ESBTP{ANNEE}-{NUMERO}
            $table->string('prefixe')->nullable(); // Préfixe spécifique (L pour Licence)
            $table->integer('annee_format'); // 2 pour 2 chiffres (24), 4 pour 4 chiffres (2024)
            $table->integer('numero_digits')->default(4); // Nombre de chiffres pour le numéro (0001, 0026, etc.)
            $table->string('etablissement_code'); // Code établissement (récupéré depuis la table établissements)
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('exemple')->nullable(); // Exemples de matricules générés
            $table->timestamps();

            // Index unique sur etablissement_id + niveau_etude_code avec nom personnalisé
            $table->unique(['etablissement_id', 'niveau_etude_code'], 'matricule_config_unique');
            $table->foreign('etablissement_id')->references('id')->on('esbtp_etablissements')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_matricule_configs');
    }
};