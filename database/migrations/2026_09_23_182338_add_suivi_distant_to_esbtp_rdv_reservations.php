<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce que MailPulse sait de la convocation APRES l'avoir acceptee.
 *
 * « Envoyee » voulait dire « MailPulse a accepte » : un courriel qui rebondit
 * deux minutes plus tard restait « envoye » pour toujours. La synchronisation
 * lit l'etat distant et le consigne ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_rdv_reservations', function (Blueprint $table) {
            $table->timestamp('convocation_delivree_at')->nullable();
            $table->timestamp('convocation_synchro_at')->nullable();
            $table->string('convocation_code_distant', 60)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_rdv_reservations', function (Blueprint $table) {
            $table->dropColumn(['convocation_delivree_at', 'convocation_synchro_at', 'convocation_code_distant']);
        });
    }
};
