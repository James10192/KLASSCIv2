<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une verification en cours ou aboutie, par demande publique.
 *
 * `demande_id` est l'identifiant PUBLIC remis au site vitrine : un UUID, pas
 * l'identifiant de la demande, qui se suit et permettrait de relancer les
 * codes d'autres familles. Le code et le jeton ne sont stockes qu'en
 * empreinte (HMAC sur la cle applicative).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_verifications_contact', function (Blueprint $table) {
            $table->id();
            $table->uuid('demande_id')->unique();
            $table->string('verifiable_type', 100);
            $table->unsignedBigInteger('verifiable_id');
            $table->string('canal', 20);
            $table->string('destination', 150);
            $table->string('code_hash', 64)->nullable();
            $table->string('jeton_hash', 64)->nullable()->unique();
            $table->timestamp('code_expire_at')->nullable();
            $table->timestamp('jeton_expire_at')->nullable();
            $table->unsignedTinyInteger('tentatives')->default(0);
            // Cumul sur tous les codes envoyes : un renvoi remet `tentatives` a zero, pas celui-ci.
            $table->unsignedSmallInteger('tentatives_total')->default(0);
            $table->string('mailpulse_verification_id', 100)->nullable();
            $table->string('mailpulse_message_id', 100)->nullable();
            $table->string('dernier_echec', 60)->nullable();
            $table->timestamp('dernier_envoi_at')->nullable();
            $table->timestamp('verifie_at')->nullable();
            $table->boolean('masque_la_demande')->default(true);
            $table->timestamps();

            $table->unique(['verifiable_type', 'verifiable_id'], 'verif_contact_verifiable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_verifications_contact');
    }
};
