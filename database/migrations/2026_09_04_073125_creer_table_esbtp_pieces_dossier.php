<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue des pieces du dossier d'inscription.
 *
 * ATTENTION — cette table appartient au Lot 1 (le catalogue). Elle est creee ici
 * au strict minimum parce que le Lot 2 (l'etat par inscription) ne peut pas tenir
 * debout sans une cible a referencer. Si le Lot 1 livre sa propre migration, c'est
 * la sienne qui fait foi : celle-ci est alors a fusionner, pas a empiler.
 *
 * Le catalogue varie PAR FILIERE ET PAR NIVEAU. Une ligne dont filiere_id et
 * niveau_id sont nuls est le defaut commun a l'ecole ; une ligne plus precise le
 * remplace pour le perimetre qu'elle vise. Raison : l'ESBTP et l'USAT n'exigent pas
 * les memes pieces, et dans une meme ecole un transfert n'apporte pas le meme
 * dossier qu'une premiere inscription.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('esbtp_pieces_dossier')) {
            return;
        }

        Schema::create('esbtp_pieces_dossier', function (Blueprint $table) {
            $table->id();

            // Le code identifie la NATURE de la piece ("extrait_naissance"). Il est
            // volontairement repetable : la meme nature peut etre redeclaree avec un
            // autre perimetre pour surcharger le defaut de l'ecole.
            $table->string('code', 64);
            $table->string('libelle');
            $table->text('description')->nullable();

            // Une piece obligatoire manquante ne bloque PAS la validation : elle
            // alimente le compteur affiche sur la fiche. Le blocage pousserait le
            // secretariat a contourner le systeme des la rentree.
            $table->boolean('is_obligatoire')->default(true);

            // Perimetre. Les deux nuls = defaut commun a l'ecole.
            $table->foreignId('filiere_id')->nullable()
                ->constrained('esbtp_filieres')->nullOnDelete();
            $table->foreignId('niveau_id')->nullable()
                ->constrained('esbtp_niveau_etudes')->nullOnDelete();

            $table->unsignedSmallInteger('ordre')->default(0);

            // Retirer une piece du catalogue se fait en la desactivant, jamais en la
            // supprimant : les dossiers deja constitues gardent la trace de ce qui a
            // ete reellement remis.
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'filiere_id', 'niveau_id'], 'idx_pieces_dossier_perimetre');
            $table->index(['code', 'is_active'], 'idx_pieces_dossier_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_pieces_dossier');
    }
};
