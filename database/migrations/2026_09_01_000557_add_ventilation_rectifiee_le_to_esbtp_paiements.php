<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quand la ventilation d'un versement a ete corrigee.
 *
 * Un recu est un papier DEJA REMIS a l'etudiant. Il porte un numero, un montant,
 * et le frais auquel l'argent a ete impute. Corriger cette imputation rend donc
 * fausse une ligne du papier qui circule.
 *
 * Reimprimer le meme numero avec un contenu different serait le pire des deux
 * mondes : deux exemplaires du recu N diraient des choses differentes, sans
 * qu'aucun ne dise lequel fait foi. On ne reecrit donc pas le recu — on le rend
 * AUTO-DECLARANT : tout exemplaire edite apres une correction porte la mention
 * datee de la rectification, et le detail de la nouvelle ventilation. Celui qui
 * compare deux copies voit immediatement laquelle est la rectifiee, et de quand.
 *
 * Cette date pourrait se lire dans la table d'audit, ou la correction est deja
 * tracee. Mais un gabarit PDF qui interroge le journal d'audit pour savoir quoi
 * imprimer melange la preuve et l'affichage : le jour ou l'audit est purge,
 * archive ou filtre, le recu se remet silencieusement a mentir. La date vit donc
 * sur le versement lui-meme.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->timestamp('ventilation_rectifiee_le')
                ->nullable()
                ->after('frais_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->dropColumn('ventilation_rectifiee_le');
        });
    }
};
