<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des rendez-vous reprogrammes au guichet. On n'y ecrit qu'en ajout.
 *
 * Une colonne « derniere absence » sur la reservation s'effacait a la seconde
 * absence : le premier jour perdait la trace de la famille qu'il n'avait pas
 * prise en charge. Ici chaque deplacement reste, avec le creneau quitte, et
 * `non_venue` dit si la famille avait manque ce creneau (termine sans elle)
 * ou si le rendez-vous a seulement ete avance ou recule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_rdv_reprogrammations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('esbtp_rdv_reservations')->cascadeOnDelete();
            $table->foreignId('creneau_quitte_id')->nullable()->constrained('esbtp_rdv_creneaux')->nullOnDelete();
            $table->foreignId('creneau_nouveau_id')->nullable()->constrained('esbtp_rdv_creneaux')->nullOnDelete();
            $table->boolean('non_venue')->default(false);
            $table->foreignId('par')->nullable()->constrained('users')->nullOnDelete();
            // Pose par l'application, pas par MySQL : une colonne useCurrent() suit le
            // fuseau de la session MySQL, pas celui de l'instance.
            $table->timestamp('created_at')->nullable();

            $table->index(['creneau_quitte_id', 'non_venue'], 'idx_rdv_reprog_quitte');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_rdv_reprogrammations');
    }
};
