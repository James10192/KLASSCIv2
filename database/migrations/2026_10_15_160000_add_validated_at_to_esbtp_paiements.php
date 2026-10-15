<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            // Aucun backfill : la DATE historique date_validation ne contient pas d'heure.
            $table->dateTime('validated_at')->nullable()->after('date_validation');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->dropColumn('validated_at');
        });
    }
};
