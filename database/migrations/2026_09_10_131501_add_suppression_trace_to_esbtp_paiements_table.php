<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qui a supprimé un versement, et pourquoi.
 *
 * La suppression est logique (deleted_at) et le journal d'audit garde la ligne,
 * mais rien ne portait le motif ni l'auteur du geste : la seule trace était un
 * horodatage. Un contrôle qui retrouve un versement supprimé doit pouvoir lire
 * la raison sans reconstituer l'histoire depuis les journaux techniques.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->foreignId('deleted_by')->nullable()->after('deleted_at')
                ->constrained('users')->nullOnDelete();
            $table->text('motif_suppression')->nullable()->after('deleted_by');
        });
    }

    public function down()
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropColumn('motif_suppression');
        });
    }
};
