<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            // date_paiement et date_validation sont des DATE metier historiques.
            // Ne jamais les convertir en DATETIME : le 00:00 serait invente.
            $table->dateTime('validation_recorded_at')->nullable()->after('date_validation');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->dropColumn('validation_recorded_at');
        });
    }
};
