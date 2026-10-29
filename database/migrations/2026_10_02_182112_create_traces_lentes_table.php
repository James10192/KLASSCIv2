<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les actions lentes : requêtes web, travaux en file, commandes. Une ligne
 * n'est écrite qu'au-dessus du seuil de l'école ou en cas d'échec, et purgée à
 * trente jours (traces:purger). Aucune valeur saisie n'y entre : la requête SQL
 * la plus lente est gardée réduite à sa forme, valeurs remplacées par « ? ».
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('traces_lentes')) {
            return;
        }

        Schema::create('traces_lentes', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16)->comment('requete | travail | commande');
            $table->string('nom', 191)->comment('Route nommée, classe de tâche ou nom de commande');
            $table->unsignedInteger('duree_ms');
            $table->unsignedInteger('requetes_sql')->default(0);
            $table->unsignedInteger('temps_sql_ms')->default(0);
            $table->unsignedSmallInteger('memoire_mo')->default(0);
            $table->smallInteger('code')->nullable()->comment('Statut HTTP, code de sortie, ou 0/1 pour un travail');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['nom', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traces_lentes');
    }
};
