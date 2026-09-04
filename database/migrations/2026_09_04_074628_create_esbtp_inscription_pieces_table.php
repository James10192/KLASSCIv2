<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etat des pieces, PAR INSCRIPTION.
 *
 * Point structurant : l'etat vit sur l'inscription, jamais sur l'etudiant.
 * « Ils prennent les documents des etudiants pour trois ans ; la, chaque annee,
 * ils prennent un exemplaire de chaque pour donner aux ministeres. » Un etudiant
 * en troisieme annee a donc TROIS lignes « extrait de naissance », une par annee,
 * et c'est voulu : chaque annee constitue son propre dossier a remettre.
 *
 * A ne pas confondre avec esbtp_inscriptions.is_sous_reserve : une piece manquante
 * porte sur un document qui EXISTE et n'a pas ete apporte ; une reserve porte sur un
 * document qui N'EXISTE PAS ENCORE. Les deux mecanismes restent separes.
 *
 * L'absence de ligne vaut « manquant » : une ecole qui vient de configurer son
 * catalogue voit immediatement l'etat reel sans backfill prealable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_inscription_pieces', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inscription_id')
                ->constrained('esbtp_inscriptions')->cascadeOnDelete();
            $table->foreignId('piece_id')
                ->constrained('esbtp_pieces_dossier')->cascadeOnDelete();

            // manquant | fourni | non_applicable
            $table->string('statut', 20)->default('manquant');

            $table->unsignedTinyInteger('exemplaires_fournis')->default(0);
            $table->date('date_fourniture')->nullable();
            $table->foreignId('recu_par')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire')->nullable();

            $table->timestamps();

            $table->unique(['inscription_id', 'piece_id'], 'inscription_pieces_unique');
            $table->index(['statut', 'piece_id'], 'inscription_pieces_statut_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_inscription_pieces');
    }
};
