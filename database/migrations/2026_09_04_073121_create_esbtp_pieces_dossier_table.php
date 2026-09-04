<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogue des pieces attendues au dossier d'inscription.
     *
     * Le catalogue est volontairement VIDE a la creation : tant qu'une ecole
     * n'a rien configure, aucun ecran existant ne change de comportement. Deux
     * ecoles n'exigent pas les memes pieces, et a l'interieur d'une meme ecole
     * un transfert n'apporte pas le meme dossier qu'une premiere inscription :
     * d'ou la variation par filiere ET par niveau, avec un defaut commun
     * (filiere_id et niveau_id a NULL) qui sert de socle a l'etablissement.
     */
    public function up(): void
    {
        Schema::create('esbtp_pieces_dossier', function (Blueprint $table) {
            $table->id();

            // `code` identifie la piece a travers toutes ses declinaisons : la
            // ligne « extrait de naissance » du defaut ecole et celle d'une
            // filiere partagent le meme code. C'est ce code, et non l'id, que
            // l'etat porte cote inscription, pour survivre a une reconfiguration.
            $table->string('code', 60);
            $table->string('libelle', 150);
            $table->string('description', 255)->nullable();

            // Obligatoire = son absence se signale sur la fiche. Elle ne bloque
            // jamais la validation : un blocage pousserait le secretariat a
            // contourner le systeme des la rentree.
            $table->boolean('est_obligatoire')->default(true);

            $table->foreignId('filiere_id')->nullable()
                ->constrained('esbtp_filieres')->nullOnDelete();
            $table->foreignId('niveau_id')->nullable()
                ->constrained('esbtp_niveau_etudes')->nullOnDelete();

            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Une seule declinaison d'un code par portee.
            $table->unique(['code', 'filiere_id', 'niveau_id'], 'pieces_dossier_portee_unique');
            $table->index(['filiere_id', 'niveau_id', 'is_active'], 'pieces_dossier_resolution_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_pieces_dossier');
    }
};
