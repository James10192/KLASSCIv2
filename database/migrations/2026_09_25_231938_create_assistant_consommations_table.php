<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une ligne par modèle appelé dans un échange avec l'assistant : ce qu'il a
 * consommé et ce que ça a coûté. Source du budget mensuel de l'école et de
 * la remontée vers adminKlassci (synchronise_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_consommations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->unsignedBigInteger('message_id')->nullable()->index();
            // question | titre | action | import | juge
            $table->string('fonction', 20)->default('question');
            $table->string('modele', 60);
            $table->string('fournisseur', 30);
            $table->string('identifiant_modele', 120);
            $table->string('palier', 20)->nullable();
            $table->unsignedInteger('appels')->default(1);
            $table->unsignedInteger('tokens_entree')->default(0);
            $table->unsignedInteger('tokens_sortie')->default(0);
            $table->unsignedInteger('tokens_cache')->default(0);
            $table->decimal('cout_usd', 12, 6)->default(0);
            $table->decimal('cout_fcfa', 12, 2)->default(0);
            $table->decimal('taux_usd_fcfa', 8, 2);
            // Coût renvoyé par le fournisseur (true) ou calculé sur le tarif déclaré.
            $table->boolean('cout_exact')->default(false);
            // ok | erreur | interrompu | limite | echec_fournisseur (modèle abandonné pour le suivant)
            $table->string('statut', 20)->default('ok');
            $table->unsignedInteger('latence_ms')->default(0);
            $table->timestamp('synchronise_at')->nullable()->index();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_consommations');
    }
};
