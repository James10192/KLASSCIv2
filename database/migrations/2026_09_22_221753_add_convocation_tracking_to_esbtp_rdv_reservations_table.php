<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'etat de la convocation vit sur la reservation.
 *
 * Avant, rien ne disait si un courriel etait parti : seul `rdv_invite_at` du
 * porteur etait pose, apres succes. Une convocation perdue ne laissait donc
 * aucune ligne, ni ici ni chez MailPulse.
 *
 * `convocation_statut` NULL veut dire « avant ce suivi » : on ne sait pas.
 * Ce n'est pas « en attente » a dessein — en attente serait envoye par la tache
 * planifiee, et plusieurs centaines de courriels partiraient au deploiement sans
 * que l'ecole l'ait decide. L'ecran propose de les envoyer ; il ne le fait pas seul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_rdv_reservations', function (Blueprint $table) {
            $table->string('convocation_statut', 20)->nullable()->after('libere_at');
            $table->string('convocation_action', 12)->nullable()->after('convocation_statut');
            $table->unsignedTinyInteger('convocation_tentatives')->default(0)->after('convocation_action');
            $table->timestamp('convocation_envoyee_at')->nullable()->after('convocation_tentatives');
            $table->string('convocation_erreur', 255)->nullable()->after('convocation_envoyee_at');
            $table->string('convocation_message_id', 100)->nullable()->after('convocation_erreur');

            $table->index('convocation_statut', 'idx_rdv_reservations_convocation');
        });

        // Une convocation reussie posait `rdv_invite_at` sur le porteur : c'est la
        // seule preuve d'envoi anterieure a ce suivi. On la reporte, rien de plus.
        foreach (['candidature_id' => 'esbtp_candidatures', 'reinscription_demande_id' => 'esbtp_reinscription_demandes'] as $cle => $table) {
            DB::table('esbtp_rdv_reservations')
                ->whereNull('convocation_statut')
                ->whereExists(function ($q) use ($cle, $table) {
                    $q->selectRaw('1')
                        ->from($table)
                        ->whereColumn($table.'.id', 'esbtp_rdv_reservations.'.$cle)
                        ->whereNotNull($table.'.rdv_invite_at');
                })
                ->update(['convocation_statut' => 'envoyee', 'convocation_action' => 'confirme']);
        }
    }

    public function down(): void
    {
        Schema::table('esbtp_rdv_reservations', function (Blueprint $table) {
            $table->dropIndex('idx_rdv_reservations_convocation');
            $table->dropColumn([
                'convocation_statut',
                'convocation_action',
                'convocation_tentatives',
                'convocation_envoyee_at',
                'convocation_erreur',
                'convocation_message_id',
            ]);
        });
    }
};
