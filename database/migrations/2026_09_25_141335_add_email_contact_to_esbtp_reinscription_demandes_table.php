<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'adresse qu'une famille de reinscription donne elle-meme sur le portail.
 *
 * Elle vit sur la demande, jamais sur la fiche de l'etudiant : le site public
 * ne reecrit pas l'e-mail officiel d'un etudiant. Elle sert a la verification
 * du contact et a la convocation ; l'ecole la reprend dans le dossier si elle
 * le souhaite.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('esbtp_reinscription_demandes', 'email_contact')) {
            return;
        }

        Schema::table('esbtp_reinscription_demandes', function (Blueprint $table) {
            $table->string('email_contact', 150)->nullable()->after('reference_publique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('esbtp_reinscription_demandes', 'email_contact')) {
            return;
        }

        Schema::table('esbtp_reinscription_demandes', function (Blueprint $table) {
            $table->dropColumn('email_contact');
        });
    }
};
