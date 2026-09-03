<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Donne a une filiere le moyen de dire qu'elle est le reflet d'une entite LMD.
 *
 * `esbtp_classes.filiere_id` est NOT NULL et pointe sur `esbtp_filieres`. En
 * LMD, la classe se rattache pourtant a une mention ou a un parcours. La
 * convention en place faisait tenir cette colonne par un hasard d'identifiants
 * (« la mention 3 tombe sur la filiere 3 »), et le hasard s'arrete des qu'une
 * ecole cree une huitieme mention alors qu'elle n'a que cinq filieres : USAT
 * n'a pu creer aucune classe pour ses trois mentions d'agronomie.
 *
 * Une filiere miroir est donc creee pour l'entite LMD qui n'en a pas, et ces
 * deux colonnes disent laquelle elle reflete.
 *
 * Pourquoi une marque explicite plutot que « la filiere est pointee par un
 * parcours » : sur les instances mixtes, un parcours LMD pointe legitimement
 * vers une VRAIE filiere BTS equivalente (c'est la retro-compat des
 * planifications). La presence d'un parcours ne distingue donc pas un miroir
 * d'un jumeau legitime. Seule une marque posee a la creation le peut.
 *
 * RESTRICT et non CASCADE : `esbtp_classes.filiere_id` est lui-meme en CASCADE.
 * Un CASCADE ici ferait disparaitre les classes d'un parcours supprime par
 * megarde, en silence. On prefere refuser la suppression.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_filieres', function (Blueprint $table) {
            if (! Schema::hasColumn('esbtp_filieres', 'lmd_mention_id')) {
                $table->foreignId('lmd_mention_id')
                    ->nullable()
                    ->constrained('esbtp_lmd_mentions')
                    ->restrictOnDelete();
                $table->unique('lmd_mention_id', 'filieres_miroir_mention_unique');
            }

            if (! Schema::hasColumn('esbtp_filieres', 'lmd_parcours_id')) {
                $table->foreignId('lmd_parcours_id')
                    ->nullable()
                    ->constrained('esbtp_lmd_parcours')
                    ->restrictOnDelete();
                $table->unique('lmd_parcours_id', 'filieres_miroir_parcours_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_filieres', function (Blueprint $table) {
            // Pas de dropUnique() ici : MySQL refuse de supprimer un index qui
            // sert de support a une cle etrangere (erreur 1553). L'index tombe
            // de lui-meme avec la colonne.
            if (Schema::hasColumn('esbtp_filieres', 'lmd_parcours_id')) {
                $table->dropConstrainedForeignId('lmd_parcours_id');
            }

            if (Schema::hasColumn('esbtp_filieres', 'lmd_mention_id')) {
                $table->dropConstrainedForeignId('lmd_mention_id');
            }
        });
    }
};
