<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supprimer une categorie de frais ne doit pas effacer des ecritures.
 *
 * `esbtp_paiement_allocations.frais_category_id` etait en `cascadeOnDelete`
 * alors que `esbtp_paiements.frais_category_id` est en `set null`. Les deux
 * tables disaient donc deux choses opposees de la meme suppression : le
 * paiement survivait en perdant sa categorie, ses lignes de repartition
 * DISPARAISSAIENT.
 *
 * Consequence pour un versement reparti : ses allocations effacees, il retombe
 * dans la branche « sans allocation » de netPaidByCategory(), qui l'impute en
 * entier a sa `frais_category_id` — desormais nulle. Il sort alors de TOUS les
 * totaux par categorie. L'argent avait bien ete encaisse, les frais qu'il
 * couvrait redeviennent dus, et rien ne le signale.
 *
 * Sur un registre comptable, une ecriture ne se supprime pas par effet de bord.
 * `restrictOnDelete` fait echouer la suppression de la categorie tant qu'elle
 * porte des allocations : la question revient a l'ecole, qui doit d'abord dire
 * ce que devient l'argent impute dessus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_paiement_allocations', function (Blueprint $table) {
            $table->dropForeign(['frais_category_id']);

            $table->foreign('frais_category_id')
                ->references('id')
                ->on('esbtp_frais_categories')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_paiement_allocations', function (Blueprint $table) {
            $table->dropForeign(['frais_category_id']);

            $table->foreign('frais_category_id')
                ->references('id')
                ->on('esbtp_frais_categories')
                ->cascadeOnDelete();
        });
    }
};
