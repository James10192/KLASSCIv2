<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'avis d'une personne sur une réponse de l'assistant (👍 / 👎), et la demande
 * KLASSCI Care qui l'a éventuellement suivie. Une ligne par message et par personne.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('assistant_retours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('chatbot_messages')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('avis', 12)->comment('utile | pas_utile');
            $table->string('raison', 20)->nullable()->comment('faux | incomplet | incompris | autre');
            $table->string('commentaire', 1000)->nullable();
            $table->string('modele', 64)->nullable()->comment('clé du modèle qui a répondu (assistant_consommations)');
            $table->string('palier', 16)->nullable();
            $table->string('care_reference', 64)->nullable()->comment('demande KLASSCI Care ouverte depuis ce retour');
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
            $table->index(['avis', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('assistant_retours');
    }
};
