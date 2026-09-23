<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verification du contact (e-mail ou WhatsApp) des demandes deposees sur le
 * portail public. Les lignes existantes restent a NULL : elles sont d'avant
 * la verification et restent visibles comme avant.
 */
return new class extends Migration
{
    private const TABLES = ['esbtp_candidatures', 'esbtp_reinscription_demandes'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('verification_contact', 30)->nullable()->index();
                $t->timestamp('email_verifie_at')->nullable();
                $t->timestamp('telephone_verifie_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['verification_contact']);
                $t->dropColumn(['verification_contact', 'email_verifie_at', 'telephone_verifie_at']);
            });
        }
    }
};
