<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le relais WhatsApp de la convocation, quand le courriel n'a pas pu la porter.
 *
 * Canal distinct de `convocation_statut` (le courriel) : une famille sans
 * adresse reste « sans e-mail » ; c'est ici que l'on suit l'accord demande,
 * l'envoi et la remise de la convocation sur WhatsApp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_rdv_reservations', function (Blueprint $table) {
            $table->string('whatsapp_statut', 30)->nullable();
            $table->unsignedSmallInteger('whatsapp_tentative')->default(0);
            $table->string('whatsapp_idempotency_key', 128)->nullable()->unique();
            $table->string('whatsapp_operation_id', 100)->nullable();
            $table->timestamp('whatsapp_demandee_at')->nullable();
            $table->timestamp('whatsapp_accord_demande_at')->nullable();
            $table->timestamp('whatsapp_accord_at')->nullable();
            $table->timestamp('whatsapp_envoyee_at')->nullable();
            $table->timestamp('whatsapp_remise_at')->nullable();
            $table->string('whatsapp_erreur', 255)->nullable();
            $table->unsignedBigInteger('whatsapp_demandee_par')->nullable();
            $table->index('whatsapp_statut');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_rdv_reservations', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_statut']);
            $table->dropUnique(['whatsapp_idempotency_key']);
            $table->dropColumn([
                'whatsapp_statut', 'whatsapp_tentative', 'whatsapp_idempotency_key', 'whatsapp_operation_id',
                'whatsapp_demandee_at', 'whatsapp_accord_demande_at', 'whatsapp_accord_at',
                'whatsapp_envoyee_at', 'whatsapp_remise_at', 'whatsapp_erreur', 'whatsapp_demandee_par',
            ]);
        });
    }
};
