<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            $table->timestamp('rdv_invite_at')->nullable();
        });

        Schema::table('esbtp_reinscription_demandes', function (Blueprint $table) {
            $table->timestamp('rdv_invite_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            $table->dropColumn('rdv_invite_at');
        });

        Schema::table('esbtp_reinscription_demandes', function (Blueprint $table) {
            $table->dropColumn('rdv_invite_at');
        });
    }
};
