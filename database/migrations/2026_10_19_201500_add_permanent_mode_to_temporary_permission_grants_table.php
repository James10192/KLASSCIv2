<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_permission_grants', function (Blueprint $table) {
            // Le false par defaut preserve strictement les acces historiques.
            // expires_at NULL n'est jamais interprete seul comme « definitif ».
            $table->boolean('is_permanent')->default(false)->after('expires_at');
            $table->index(['user_id', 'permission', 'revoked_at'], 'tpg_personne_permission_retrait_idx');
        });
    }

    public function down(): void
    {
        Schema::table('temporary_permission_grants', function (Blueprint $table) {
            $table->dropIndex('tpg_personne_permission_retrait_idx');
            $table->dropColumn('is_permanent');
        });
    }
};
