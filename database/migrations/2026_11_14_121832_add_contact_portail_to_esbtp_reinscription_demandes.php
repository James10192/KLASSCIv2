<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le contact que la famille choisit sur le portail de reinscription.
 *
 * `email_contact` : l'adresse qu'elle lit vraiment, quand celle du dossier est
 * ancienne ou absente. Rangee sur la DEMANDE, pas sur l'etudiant : seuls un
 * matricule et une date de naissance ont ete prouves, et ecrire l'adresse du
 * dossier ouvrirait les reinitialisations de mot de passe a quiconque connait
 * ce couple. La convocation y part (`emailRdv`) ; la scolarite la voit sur la
 * demande et la reporte au dossier si elle le juge bon.
 *
 * `canal_prefere` : `email` ou `telephone`, pour le code de confirmation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_reinscription_demandes', function (Blueprint $table) {
            if (! Schema::hasColumn('esbtp_reinscription_demandes', 'email_contact')) {
                $table->string('email_contact', 254)->nullable();
            }
            if (! Schema::hasColumn('esbtp_reinscription_demandes', 'canal_prefere')) {
                $table->string('canal_prefere', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_reinscription_demandes', function (Blueprint $table) {
            foreach (['email_contact', 'canal_prefere'] as $colonne) {
                if (Schema::hasColumn('esbtp_reinscription_demandes', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
    }
};
