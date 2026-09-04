<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des gestes qui touchent plusieurs maquettes d'une meme unite.
 *
 * Trois mois apres coup, personne ne peut repondre a « pourquoi cette matiere
 * n'est plus dans TIR ». Un confirm() natif sur lequel on tape Entree ne laisse
 * aucune trace, et rien ne permet de revenir en arriere. Cette table garde,
 * pour chaque geste, l'etat des deux pivots AVANT et APRES : la trace est donc
 * consultable, et l'annulation consiste a reposer l'etat d'avant tel quel.
 *
 * On stocke l'etat en JSON plutot qu'un diff colonne par colonne parce que le
 * schema des pivots va bouger (le partage par parcours ajoute une colonne) : un
 * instantane pris avec SELECT * emporte les colonnes futures sans rien changer
 * ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_lmd_journal_maquette', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_enseignement_id');
            $table->string('action', 40);
            $table->string('libelle', 255);
            $table->json('etat_avant');
            $table->json('etat_apres');
            $table->timestamp('annulee_at')->nullable();
            $table->unsignedBigInteger('annulee_par')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['unite_enseignement_id', 'created_at'], 'jm_unite_date_idx');

            $table->foreign('unite_enseignement_id')
                ->references('id')->on('esbtp_unites_enseignement')
                ->onDelete('cascade');
            $table->foreign('annulee_par')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_lmd_journal_maquette');
    }
};
