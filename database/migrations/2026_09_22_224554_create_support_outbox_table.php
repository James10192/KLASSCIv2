<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KLASSCI Care : les signalements que le Master n'a pas pu recevoir.
 *
 * Un signalement ne se perd pas parce que le reseau est tombe. Il attend ici,
 * et le planificateur le renvoie (support:vider-boite-envoi) avec la MEME cle
 * d'idempotence : si le premier envoi etait en fait arrive, le Master rend la
 * demande deja creee au lieu d'en creer une seconde.
 *
 * Seule table KLASSCI Care cote instance : les demandes elles-memes vivent au
 * Master.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 64)->unique();
            $table->json('payload');
            $table->string('request_id', 64)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->string('reference', 24)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamps();

            $table->index(['sent_at', 'abandoned_at', 'next_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_outbox');
    }
};
