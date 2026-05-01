<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_relances', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_relances', 'canal')) {
                $table->string('canal', 40)->nullable()->after('type')
                    ->comment('Canal utilisé : whatsapp_deeplink, sms, email, tel, manuel');
            }
            if (!Schema::hasColumn('esbtp_relances', 'inscription_id')) {
                $table->foreignId('inscription_id')->nullable()->after('etudiant_id')
                    ->constrained('esbtp_inscriptions')->nullOnDelete();
            }
            if (!Schema::hasColumn('esbtp_relances', 'declenchee_par')) {
                $table->foreignId('declenchee_par')->nullable()->after('statut')
                    ->constrained('users')->nullOnDelete()
                    ->comment('Utilisateur ayant déclenché la relance (intent ou confirmé)');
            }
            if (!Schema::hasColumn('esbtp_relances', 'confirmee_a')) {
                $table->timestamp('confirmee_a')->nullable()->after('date_envoi')
                    ->comment('Quand le comptable a confirmé que la relance a été effectivement envoyée');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_relances', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_relances', 'confirmee_a')) {
                $table->dropColumn('confirmee_a');
            }
            if (Schema::hasColumn('esbtp_relances', 'declenchee_par')) {
                $table->dropConstrainedForeignId('declenchee_par');
            }
            if (Schema::hasColumn('esbtp_relances', 'inscription_id')) {
                $table->dropConstrainedForeignId('inscription_id');
            }
            if (Schema::hasColumn('esbtp_relances', 'canal')) {
                $table->dropColumn('canal');
            }
        });
    }
};
