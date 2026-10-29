<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce que l'instance a deja vu des demandes KLASSCI Care, pour avertir le
 * rapporteur quand le support repond ou cloture — une fois, pas a chaque
 * passage du planificateur.
 *
 * Les demandes elles-memes restent au Master : on ne garde ici que le dernier
 * etat vu (statut, date de la derniere reponse du support), et un curseur.
 * En table et non en cache : un `cache:clear` de deploiement ne doit ni faire
 * oublier le curseur (avertissements perdus) ni le remettre a zero
 * (historique renotifie).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_demandes_suivies', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('statut_code', 24)->nullable();
            $table->timestamp('derniere_reponse_support_le')->nullable();
            $table->timestamp('mis_a_jour_le')->nullable();
            $table->timestamp('averti_le')->nullable();
            $table->timestamps();
        });

        Schema::create('support_curseurs', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 64)->unique();
            $table->timestamp('valeur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_curseurs');
        Schema::dropIfExists('support_demandes_suivies');
    }
};
