<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etat des pieces du dossier, PAR INSCRIPTION.
 *
 * Le point structurant du besoin : l'etat vit sur l'INSCRIPTION, jamais sur
 * l'etudiant. L'ecole reprend un exemplaire de chaque piece a chaque rentree pour
 * le transmettre aux ministeres ; un etudiant en troisieme annee de licence a donc
 * TROIS lignes "extrait de naissance", une par annee. Ce n'est pas une duplication
 * a corriger, c'est le besoin.
 *
 * A ne pas confondre avec la reserve (is_sous_reserve / condition_reserve deja
 * portees par esbtp_inscriptions) : une piece manquante existe et n'a pas ete
 * apportee ; une reserve porte sur un document qui n'existe pas encore et
 * arrivera plus tard. Les deux mecanismes restent separes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_inscription_pieces', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inscription_id')
                ->constrained('esbtp_inscriptions')->cascadeOnDelete();

            // restrict : on ne perd jamais le lien vers la nature de la piece.
            // Une piece qu'on ne demande plus se desactive dans le catalogue.
            $table->foreignId('piece_dossier_id')
                ->constrained('esbtp_pieces_dossier')->restrictOnDelete();

            // fournie / manquante / non_applicable — voir App\Enums\StatutPieceDossier.
            // Chaine et non enum SQL : ajouter un statut ne doit pas demander une
            // migration sur deux bases de plus de 2000 inscrits.
            $table->string('statut', 32)->default('manquante');

            $table->date('date_remise')->nullable();

            // Qui a constate la remise. Distinct de updated_by : c'est la personne
            // qui engage l'ecole en disant "j'ai eu le papier en main".
            $table->foreignId('constate_par')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->text('note')->nullable();

            // Le fichier est facultatif : beaucoup d'ecoles archivent le papier et
            // ne numerisent rien. Le dossier doit tenir sans.
            $table->string('fichier_path')->nullable();
            $table->string('fichier_nom')->nullable();
            $table->unsignedBigInteger('fichier_taille')->nullable();
            $table->string('fichier_type', 128)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Garantit l'idempotence de la materialisation au niveau de la base :
            // rouvrir une inscription ne peut pas recreer une ligne deja saisie,
            // meme si deux requetes arrivent en meme temps.
            $table->unique(['inscription_id', 'piece_dossier_id'], 'uniq_inscription_piece');

            $table->index(['inscription_id', 'statut'], 'idx_inscription_piece_statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_inscription_pieces');
    }
};
