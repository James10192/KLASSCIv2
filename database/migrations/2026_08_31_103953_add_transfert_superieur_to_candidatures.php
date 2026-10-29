<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le candidat qui ne sort pas du lycee.
 *
 * La table a ete ecrite pour un bachelier : serie, annee, etablissement
 * d'origine — le lycee. Or une part des nouvelles inscriptions vient d'un
 * autre etablissement SUPERIEUR, et ces trois champs ne disent alors rien de
 * ce que l'ecole a besoin de savoir : d'ou il vient, ce qu'il y a suivi, ce
 * qu'il y a valide. L'agent devait rappeler la famille pour l'apprendre.
 *
 * Le bac reste demande dans les deux cas : un transfere en a un, et c'est la
 * piece que l'ecole verifie en premier. Ce qui s'ajoute ici ne le remplace
 * pas, il se pose a cote.
 *
 * `est_transfert` porte le meme nom que sur esbtp_inscriptions a dessein.
 * C'est un DRAPEAU, pas un type : une candidature est toujours une premiere
 * inscription chez nous — ce qui change, c'est d'ou vient le candidat. La
 * migration voisine retire d'ailleurs « transfert » de type_inscription, ou
 * il n'aurait jamais du etre. Nom identique des deux cotes = le
 * pre-remplissage est une recopie, sans table de correspondance a maintenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            // Defaut `false` plutot que nullable : les candidatures deja
            // deposees SONT des bacheliers — la question ne leur a jamais ete
            // posee parce qu'elle n'existait pas, pas parce qu'ils ont refuse
            // d'y repondre. Un nullable ferait porter a l'agent un doute que
            // l'histoire ne justifie pas.
            $table->boolean('est_transfert')->default(false)->after('annee_bac');

            // L'etablissement quitte. Le seul obligatoire quand la case est
            // cochee : sans lui, la declaration de transfert n'apprend rien.
            //
            // Volontairement distinct de `etablissement_origine`, qui garde
            // son sens de lycee du bac. Les confondre reviendrait a ecraser le
            // lycee d'un transfere — ou a lire un lycee la ou l'ecole attend
            // une universite. Meme mot, deux choses.
            $table->string('etablissement_sup_origine', 150)->nullable()->after('est_transfert');

            $table->string('formation_origine', 150)->nullable()->after('etablissement_sup_origine');
            $table->string('niveau_atteint_origine', 60)->nullable()->after('formation_origine');

            // `year` et non `date` : personne ne retient le jour de sa derniere
            // inscription, et demander une date complete produit une reponse
            // inventee.
            $table->year('annee_derniere_inscription')->nullable()->after('niveau_atteint_origine');

            // Texte libre : les motifs reels (demenagement, filiere fermee,
            // reorientation, echec) ne tiennent pas dans une liste, et une
            // liste fermee pousserait a cocher « autre » sans rien expliquer.
            $table->text('motif_transfert')->nullable()->after('annee_derniere_inscription');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            $table->dropColumn([
                'est_transfert',
                'etablissement_sup_origine',
                'formation_origine',
                'niveau_atteint_origine',
                'annee_derniere_inscription',
                'motif_transfert',
            ]);
        });
    }
};
