<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue des pieces a fournir au dossier d'inscription.
 *
 * Pourquoi une table plutot qu'une liste en dur : USAT et l'ESBTP n'exigent pas
 * les memes pieces, et a l'interieur d'une meme ecole un transfert n'apporte pas
 * le meme dossier qu'une premiere inscription. Le scope (filiere_id, niveau_id)
 * porte cette variabilite, et la ligne (NULL, NULL) tient lieu de defaut commun
 * a l'etablissement.
 *
 * Pourquoi le catalogue peut rester vide : tant qu'une ecole n'a rien configure,
 * aucune piece n'est attendue, donc aucun ecran existant ne change de comportement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_pieces_dossier', function (Blueprint $table) {
            $table->id();

            // Le code identifie la piece a travers les scopes : c'est lui qui permet
            // d'agreger « il manque 34 extraits de naissance » toutes filieres confondues.
            $table->string('code', 60);
            $table->string('libelle', 150);
            $table->text('description')->nullable();

            // Scope : NULL = vaut pour tout l'etablissement. Le plus specifique gagne.
            $table->foreignId('filiere_id')->nullable()
                ->constrained('esbtp_filieres')->nullOnDelete();
            $table->foreignId('niveau_id')->nullable()
                ->constrained('esbtp_niveau_etudes')->nullOnDelete();

            $table->boolean('est_obligatoire')->default(true);

            // « Chaque annee, ils prennent un exemplaire de chaque pour donner aux
            // ministeres » : le nombre d'exemplaires attendus est donc une donnee metier.
            $table->unsignedTinyInteger('nombre_exemplaires')->default(1);

            $table->unsignedSmallInteger('ordre')->default(0);

            // is_active = false sur une ligne de scope sert d'exemption explicite :
            // « cette filiere n'exige PAS la piece pourtant exigee par defaut ».
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Une seule definition par (code, scope). MySQL laisse passer les doublons
            // de NULL : l'unicite du defaut (NULL, NULL) reste a la charge de l'appel.
            $table->unique(['code', 'filiere_id', 'niveau_id'], 'pieces_dossier_code_scope_unique');
            $table->index(['filiere_id', 'niveau_id'], 'pieces_dossier_scope_index');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_pieces_dossier');
    }
};
