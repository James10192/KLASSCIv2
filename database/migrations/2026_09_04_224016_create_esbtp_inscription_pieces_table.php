<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La consommation : ce qu'UNE inscription prend sur le stock déposé.
 *
 * Six photos déposées, deux consommées la première année, deux la deuxième : il
 * en reste deux. Le disponible ne se décrémente jamais, il se CALCULE —
 *
 *     disponible = déposé non périmé − consommé par les inscriptions retenues
 *
 * — et « retenues » est un `where` piloté par le réglage d'école
 * `pieces_dossier.restitution_annulation`. L'annulation, la suppression et la
 * double décrémentation deviennent alors une condition de lecture, et non trois
 * écritures compensatoires à tenir. Une inscription annulée cesse de consommer
 * sans qu'aucun code n'ait à le rendre.
 *
 * ─── Pourquoi il n'y a ni colonne générée ni sentinelle zéro ici ───
 *
 * Le plan de ce lot prévoyait `unique(etudiant_id, piece_dossier_id,
 * inscription_id)` posé sur une colonne générée `COALESCE(inscription_id, 0)`,
 * au motif qu'un index unique portant une colonne nulle laisse passer les
 * doublons. Deux raisons de ne pas le faire, et la seconde est la vraie.
 *
 * 1. MySQL 8 REFUSE une clé étrangère `SET NULL` sur la colonne de base d'une
 *    colonne générée STORED. Le schéma ne se créerait pas. Le dépôt connaît déjà
 *    ce mur (2026_04_23_205439_allow_global_manual_attendance_hours).
 *
 * 2. Surtout : ici, la tolérance des NULL est exactement ce qu'il faut. Une
 *    inscription supprimée laisse une ligne orpheline — `inscription_id` nul —
 *    qui doit SURVIVRE, sans quoi le stock se rendrait tout seul quel que soit le
 *    réglage de l'école. Deux inscriptions supprimées laissent DEUX orphelines
 *    pour le même couple (étudiant, pièce), et les deux doivent tenir. Sous une
 *    sentinelle zéro, la seconde entrerait en collision et serait perdue : le
 *    calcul du stock deviendrait faux, en silence.
 *
 * L'unicité utile est donc celle que le moteur donne pour rien : une ligne par
 * (inscription vivante, pièce), et les orphelines libres de s'empiler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_inscription_pieces', function (Blueprint $table) {
            $table->id();

            $table->foreignId('etudiant_id')
                ->constrained('esbtp_etudiants')
                ->cascadeOnDelete();

            $table->foreignId('piece_dossier_id')
                ->constrained('esbtp_pieces_dossier')
                ->restrictOnDelete();

            $table->foreignId('inscription_id')
                ->nullable()
                ->constrained('esbtp_inscriptions')
                ->nullOnDelete();

            $table->unsignedSmallInteger('quantite_consommee')->default(1);

            // Le seul des cinq états d'origine qui soit vraiment ANNUEL : la
            // pièce ne concerne pas cet étudiant cette année-là — un transfert
            // sans certificat de scolarité du même établissement — sans que cela
            // dise quoi que ce soit de son dépôt.
            $table->boolean('non_applicable')->default(false);
            $table->text('motif')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Une seule ligne par (inscription vivante, pièce). Les orphelines,
            // dont `inscription_id` est nul, échappent à cette unicité, et c'est
            // voulu : cf. l'en-tête.
            $table->unique(['inscription_id', 'piece_dossier_id'], 'inscription_pieces_unique');

            $table->index(['etudiant_id', 'piece_dossier_id'], 'inscription_pieces_stock_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_inscription_pieces');
    }
};
