<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_seance_cours', 'emploi_temps_id')) {
                $table->unsignedBigInteger('emploi_temps_id')->after('id')->nullable();
                $table->foreign('emploi_temps_id')->references('id')->on('esbtp_emploi_temps')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_seance_cours', 'emploi_temps_id')) {
                $table->dropForeign(['emploi_temps_id']);
                $table->dropColumn('emploi_temps_id');
            }
        });
    }
};
