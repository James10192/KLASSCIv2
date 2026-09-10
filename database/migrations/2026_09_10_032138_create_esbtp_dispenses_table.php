<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les dispenses : une matiere dont un etudiant est dispense, pour un semestre
 * ou pour l'annee, avec le motif de la decision.
 *
 * Une dispense ne s'efface pas, elle se revoque : la ligne reste, horodatee et
 * signee des deux cotes. Un bulletin distribue s'appuie dessus, et il faudra
 * pouvoir dire des mois plus tard qui a decide quoi.
 *
 * L'unicite « une seule dispense active par (etudiant, matiere, annee, periode) »
 * n'est pas une contrainte de base : les lignes revoquees restent, et elles
 * occuperaient la cle. C'est le service qui la tient, dans une transaction.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('esbtp_dispenses')) {
            return;
        }

        Schema::create('esbtp_dispenses', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->restrictOnDelete();
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->restrictOnDelete();
            $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->restrictOnDelete();

            // 'semestre1', 'semestre2', ou null pour toute l'annee.
            $table->string('periode', 20)->nullable();

            // Le motif est obligatoire : une dispense sans raison ecrite ne se
            // defend pas devant la famille, ni six mois plus tard.
            $table->string('motif', 160);

            $table->foreignId('accordee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('accordee_le');

            $table->foreignId('revoquee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('revoquee_le')->nullable();
            $table->string('motif_revocation', 160)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['etudiant_id', 'annee_universitaire_id'], 'idx_dispense_etudiant_annee');
            $table->index(['matiere_id', 'annee_universitaire_id'], 'idx_dispense_matiere_annee');
            // Sert la recherche des dispenses actives d'une cohorte.
            $table->index(['annee_universitaire_id', 'revoquee_le'], 'idx_dispense_annee_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_dispenses');
    }
};
