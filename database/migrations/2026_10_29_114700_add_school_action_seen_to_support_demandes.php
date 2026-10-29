<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_demandes_suivies', function (Blueprint $table) {
            $table->timestamp('derniere_action_administration_le')->nullable();
            $table->timestamp('action_administration_en_attente_le')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('support_demandes_suivies', function (Blueprint $table) {
            $table->dropColumn('derniere_action_administration_le');
        });
    }
};
