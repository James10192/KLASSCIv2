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
            $table->foreignId('emploi_temps_id')->after('id')->constrained('esbtp_emploi_temps')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            $table->dropForeign(['emploi_temps_id']);
            $table->dropColumn('emploi_temps_id');
        });
    }
};
