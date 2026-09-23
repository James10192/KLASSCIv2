<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La trace de l'accueil au guichet.
 *
 * `statut` dit deja si la famille est venue (honoree) ou non (manquee), mais
 * pas qui l'a constate ni quand. Et une famille absente qu'on reprogramme
 * redevient « confirmee » sur un autre creneau : sans compteur, son absence
 * s'effacerait. `absences` et `dernier_creneau_manque_id` gardent ce suivi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_rdv_reservations', function (Blueprint $table) {
            $table->timestamp('accueilli_at')->nullable()->after('convocation_message_id');
            $table->foreignId('accueilli_par')->nullable()->after('accueilli_at')
                ->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('absences')->default(0)->after('accueilli_par');
            $table->foreignId('dernier_creneau_manque_id')->nullable()->after('absences')
                ->constrained('esbtp_rdv_creneaux')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_rdv_reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dernier_creneau_manque_id');
            $table->dropConstrainedForeignId('accueilli_par');
            $table->dropColumn(['accueilli_at', 'absences']);
        });
    }
};
