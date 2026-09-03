<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qu'un etablissement a deliberement ajoute a un role canonique.
 *
 * La synchronisation des permissions retire, sur cinq roles d'organigramme,
 * tout ce qui ne figure pas dans les defauts partages. C'est voulu : ces roles
 * ont accumule des permissions au fil des versions, et sans ce nettoyage la
 * derive ne se corrige jamais.
 *
 * Mais ce nettoyage ne sait pas distinguer deux choses opposees :
 *
 *   - une derive : une permission restee la par accident, a retirer ;
 *   - une decision : « chez nous, la scolarite valide les inscriptions ».
 *
 * Il traite les deux pareil, donc il efface la seconde. Une ecole qui etend un
 * role le voit revenir a l'etat d'usine au prochain deploiement, sans message.
 * Et l'ajouter aux defauts partages n'est pas une reponse : cela l'imposerait
 * aux six instances, alors que chacune a son organigramme.
 *
 * Cette table porte donc la difference. Ce qui y figure a ete voulu ; le reste
 * est de la derive. Elle vit dans la base de l'instance : ce qu'une ecole
 * decide n'a aucune raison de traverser vers les autres.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('role_permission_extensions')) {
            return;
        }

        Schema::create('role_permission_extensions', function (Blueprint $table) {
            $table->id();

            // Le NOM du role, pas son identifiant : la synchronisation raisonne
            // en noms, et un role recree — ce qui arrive quand une base est
            // reconstruite — porterait un autre identifiant sans que la
            // decision de l'ecole ait change.
            $table->string('role_name', 125);
            $table->string('permission_name', 190);

            // Pourquoi cette ecole l'a voulu. Facultatif, parce qu'exiger une
            // justification a chaque case cochee dans l'interface ferait ecrire
            // « ok » a tout le monde — et un motif vide se lit mieux qu'un motif
            // menteur.
            $table->string('motif', 500)->nullable();

            $table->unsignedBigInteger('accordee_par')->nullable();
            $table->timestamps();

            $table->unique(['role_name', 'permission_name'], 'role_perm_ext_unique');
            $table->index('role_name', 'role_perm_ext_role_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission_extensions');
    }
};
