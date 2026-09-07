<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            $table->string('reference_publique', 16)->nullable()->unique('unique_candidature_reference_publique');
        });

        Schema::table('esbtp_reinscription_demandes', function (Blueprint $table) {
            $table->string('reference_publique', 16)->nullable()->unique('unique_demande_reference_publique');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            $table->dropUnique('unique_candidature_reference_publique');
            $table->dropColumn('reference_publique');
        });

        Schema::table('esbtp_reinscription_demandes', function (Blueprint $table) {
            $table->dropUnique('unique_demande_reference_publique');
            $table->dropColumn('reference_publique');
        });
    }
};
