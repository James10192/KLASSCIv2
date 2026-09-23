<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qui a fait quoi, et quand, sur une reservation, hors envoi de courriel.
 *
 * `accueilli_*` : l'agent qui a reçu la famille au guichet, et l'heure. Le
 * statut « honoree » disait deja qu'elle etait venue, pas qui l'a constate.
 *
 * `prevenue_par` : l'agent qui a prevenu la famille par telephone quand aucun
 * courriel n'a pu partir (statut de convocation « telephone »). L'heure est
 * dans convocation_envoyee_at, comme pour un courriel parti.
 *
 * L'absence, elle, n'est pas stockee ici : elle se deduit (creneau termine,
 * famille pas reçue), et chaque reprogrammation est journalisee dans
 * esbtp_rdv_reprogrammations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_rdv_reservations', function (Blueprint $table) {
            $table->timestamp('accueilli_at')->nullable()->after('convocation_message_id');
            $table->foreignId('accueilli_par')->nullable()->after('accueilli_at')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('prevenue_par')->nullable()->after('accueilli_par')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_rdv_reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prevenue_par');
            $table->dropConstrainedForeignId('accueilli_par');
            $table->dropColumn('accueilli_at');
        });
    }
};
